<?php
/**
 *
 * Example configuration in the config/config.php
 * Refer to the github of HEXAA
 *
 *    authproc.aa = array(
 *       ...
 *       '60' => array(
 *            'class' => 'rzonegrouper:rZoneGrouper',
 *            'nameId_attribute_name' =>  'eduPersonPrincipalName', // look at the aa authsource config
 *            'dsn' => 'mysql:host=localhost;dbname=grouper',
 *            'username' => 'grouperdb',
 *            'password' => '## DB PASSWORD ##',
 *       ),
 */
class sspmod_rzonegrouper_Auth_Process_rZoneGrouper extends SimpleSAML_Auth_ProcessingFilter
{
    private $as_config;
    
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        $params = array('dsn', 'username', 'password', 'nameId_attribute_name');
        foreach ($params as $param) {
            if (!array_key_exists($param, $config)) {
                throw new SimpleSAML_Error_Exception('Missing required attribute: ' . $param);
            }
            $this->as_config[$param] = $config[$param];
        }
    }
    
    public function process(&$state) {
        assert('is_array($state)');
        $nameId = $state['Attributes'][$this->as_config['nameId_attribute_name']][0];
        $spid = $state['Destination']['entityid'];
        $state['Attributes'] = $this->getAttributes($nameId, $spid);

	SimpleSAML\Logger::debug('[aa] attr: '.var_export($state['Attributes'], true));
	
    }
    
    public function getAttributes($nameId, $spid, $attributes = array()) {
	// $nameId = ePPN
	// $spid = sp's entityId

	$attributes = array(
          'urn:oid:1.3.6.1.4.1.5923.1.5.1.1' => array('test:abc'),
          'isMemberOf' => array('test:def'),
        );
	return $attributes;

	$db = new PDO($this->as_config['dsn'], $this->as_config['username'], $this->as_config['password']);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$db->exec("SET NAMES 'utf8'");

	/* $ismemberof_string = "select distinct replace(GROUP_NAME, concat('resources:', SUBSTRING_INDEX(SUBSTRING_INDEX('$spid', '//', -1), '1', 1),
			':'), '') as GROUP_NAME from grouper_memberships_lw_v 
			where subject_id like (select subject_id from grouper_members where subject_identifier0 = '$nameId')
			and GROUP_NAME like concat('resources:', substring_index(substring_index('$spid', '//', -1), '/', 1), '%')
			and list_name = 'members'
			and GROUP_NAME not like '%:service:%'";
	$entitlement_string = "select distinct concat('urn:mace:kreonet.net:', replace(replace(GROUP_NAME, ':service:authorized', ''),
			concat(substring_index(substring_index('$spid', '//', -1), '/', 1), ':'), '')) as group_name
			from grouper_memberships_lw_v
			where subject_id like (select subject_id from grouper_members where subject_identifier0 = '$nameId')
			and GROUP_NAME like concat('resources:', substring_index(substring_index('$spid', '//', -1), '/', 1), '%')
			and list_name = 'members'
			and GROUP_NAME like '%:service:authorized'"; */

	$ismemberof_string = "select distinct replace(GROUP_NAME, concat('resources:', SUBSTRING_INDEX(SUBSTRING_INDEX(:spid, '//', -1), $
                        ':'), '') as GROUP_NAME from grouper_memberships_lw_v
                        where subject_id like (select subject_id from grouper_members where subject_identifier0 = :nameId)
                        and GROUP_NAME like concat('resources:', substring_index(substring_index(:spid, '//', -1), '/', 1), '%')
                        and list_name = 'members'
                        and GROUP_NAME not like '%:service:%'";

	$st = $db->prepare($ismemberof_string);
	if(!$st->execute(array('spid' => $spid, 'nameId' => $nameId))) {
	    throw new Exception('Failed to query database for user.');
	}

	$row = $st->fetch(PDO::FETCH_ASSOC);
	$attributes = array(
	  'urn:oid:1.3.6.1.4.1.5923.1.5.1.1' => array('test:abc'),
	  'isMemberOf' => array('test:def'),
	);

	return $attributes;
    }
}
