<?php
/*
 * Plugin Name: WP Courseware - Magic Members Add On
 * Version: 1.1
 * Plugin URI: http://flyplugins.com
 * Description: The official extension for WP Courseware to add support for the Magic Members membership plugin for WordPress.
 * Author: Fly Plugins
 * Author URI: http://flyplugins.com
 */


// Main parent class
include_once 'class_members.inc.php';

// Hook to load the class
add_action('init', 'WPCW_Members_Magic_init');

/**
 * Initialise the membership plugin, only loaded if WP Courseware 
 * exists and is loading correctly.
 */
function WPCW_Members_Magic_init()
{
	$item = new WPCW_Members_MagicMembers();
	
	// Check for WP Courseware
	if (!$item->found_wpcourseware()) {
		$item->attach_showWPCWNotDetectedMessage();
		return;
	}
	
	// Not found the membership tool
	if (!$item->found_membershipTool()) {
		$item->attach_showToolNotDetectedMessage();
		return;
	}
	
	// Found the tool and WP Coursewar, attach.
	$item->attachToTools();
}


/**
 * Membership class that handles the specifics of the Magic Members WordPress plugin and
 * handling the data for levels for that plugin.
 */
class WPCW_Members_MagicMembers extends WPCW_Members
{
	const GLUE_VERSION  = 1.00; 
	const EXTENSION_NAME = 'Magic Members';
	const EXTENSION_ID = 'WPCW_members_magic';
	
	/**
	 * Main constructor for this class.
	 */
	function __construct()
	{
		// Initialise using the parent constructor 
		parent::__construct(WPCW_Members_MagicMembers::EXTENSION_NAME, WPCW_Members_MagicMembers::EXTENSION_ID, WPCW_Members_MagicMembers::GLUE_VERSION);
	}
	
	
	/**
	 * Get the membership levels for this specific membership plugin. (id => array (of details))
	 */
	protected function getMembershipLevels()
	{
		$levelData = mgm_get_all_membership_type();
				
		if ($levelData && count($levelData) > 0)
		{
			$levelDataStructured = array();
			
			// Format the data in a way that we expect and can process
			foreach ($levelData as $levelDatum)
			{
				$levelItem = array();
				$levelItem['name'] 	= $levelDatum['name'];
				$levelItem['id'] 	= $levelDatum['code'];
				$levelItem['raw'] 	= $levelDatum;
								
				$levelDataStructured[$levelItem['id']] = $levelItem;
			}
			
			return $levelDataStructured;
		}
		
		return false;
	}

	
	/**
	 * Function called to attach hooks for handling when a user is updated or created.
	 */	
	protected function attach_updateUserCourseAccess()
	{
		// Update course access whenever the user is updated. Best that's possible with Magic Member. 
		add_action('mgm_user_options_save', 			array($this, 'handle_updateUserCourseAccess'), 10, 2);
	}

	/**
	 * Assign selected courses to members of a paticular level.
	 * @param Level ID in which members will get courses enrollment adjusted.
	 */
	protected function retroactive_assignment($level_ID)
    {
    	$page = new PageBuilder(false);

    	//Get members associated with $level_ID
		$members = mgm_get_members_with('membership_type', $level_ID);

		if (count($members) > 0){
			//Enroll members into of level
			foreach ($members as $member){
				// Get user levels
				$membership_type = mgm_get_subscribed_membershiptypes($member);
				// Over to the parent class to handle the sync of data.
				parent::handle_courseSync($member, $membership_type);
			}
				
		$page->showMessage(__('All members were successfully retroactively enrolled into the selected courses.', 'wp_courseware'));
            
        return;

		}else {
            $page->showMessage(__('No existing customers found for the specified product.', 'wp_courseware'));
        }
    }
	

	/**
	 * Function just for handling the membership callback, to interpret the parameters
	 * for the class to take over.
	 *
	 * @param Array $options Magic Members options (ignored.
	 * @param Integer $id The ID if the user being changed.
	 */
	public function handle_updateUserCourseAccess($options, $id)
	{
		// Get membership type for this user
		$mgm_member = mgm_get_member($id);
		//$membership_type = $mgm_member->membership_type;
		$membership_type = mgm_get_subscribed_membershiptypes($id);
				
		// Over to the parent class to handle the sync of data.
		parent::handle_courseSync($id, $membership_type);
	}
	
	
	/**
	 * Detect presence of the membership plugin.
	 */
	public function found_membershipTool()
	{
		return function_exists('mgm_get_all_membership_type');
	}
	
	
	
}

?>