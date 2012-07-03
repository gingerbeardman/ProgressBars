<?php if (!defined('APPLICATION')) exit();

$PluginInfo['ProgressBars'] = array(
	'Name' => 'Progress Bars',
	'Description' => 'Add ability to insert progress bars into a comment. Perhaps to show status of a work in progress.',
	'Version' 	=>	 '1.0.3',
	'MobileFriendly' => TRUE,
	'Author' 	=>	 "Matt Sephton",
	'AuthorEmail' => 'matt@gingerbeardman.com',
	'AuthorUrl' =>	 'http://www.vanillaforums.org/profile/matt',
	'SettingsUrl' => '/settings/progressbars',
	'SettingsPermission' => 'Garden.Settings.Manage',
	'License' => 'GPL v2',
	'RequiredApplications' => array('Vanilla' => '>=2'),
);

/*
 *
 * Changelog
 * 1.0.3, number can now include percentage sign % (optional)
 * 1.0.2, fully complete items are now coloured differently
 * 1.0.1, tightened spacing between successive progress bars
 * 1.0.0, initial release
 *
 */

class ProgressBarsPlugin implements Gdn_IPlugin {

	public function SettingsController_ProgressBars_Create($Sender, $Args = array()) {
		$Sender->Permission('Garden.Settings.Manage');
		$Sender->SetData('Title', T('Progress Bars'));

		$Cf = new ConfigurationModule($Sender);
		$Cf->Initialize(array(
			'Plugins.ProgressBars.LowerSplit' => array('Description' => '', 'Control' => 'DropDown', 'Items' => array('' => '10% (Default)', 20 => '20%', 30 => '30%', 40 => '40%') ),
			'Plugins.ProgressBars.UpperSplit' => array('Description' => '', 'Control' => 'DropDown', 'Items' => array('' => '80% (Default)', 70 => '70%', 60 => '60%', 50 => '50%') ),
			'Plugins.ProgressBars.Greyscale' => array('Description' => '', 'Control' => 'CheckBox' )
		));

		$Sender->AddSideMenu('dashboard/settings/plugins');
		$Cf->RenderAll();
	}
	
	/**
	 * Replace progress tags in comments.
	 */
	public function Base_AfterCommentFormat_Handler($Sender) {
		if (!C('Plugins.ProgressBars.FormatValues', TRUE))
			return;

		$Object = $Sender->EventArguments['Object'];
		$Object->FormatBody = $this->DoValues($Object->FormatBody);
		$Sender->EventArguments['Object'] = $Object;
	}
	
	public function DiscussionController_Render_Before($Sender) {
		$this->_ProgressBarsSetup($Sender);
	}

	/**
	 * Return an array of Values.
	 */
	public static function GetValues() {
		for ($i=0; $i<=100; $i++) {
			$numbers[] = (string)$i;
			if ($i < 10) $numbers[] = str_pad($i, 2, '0', STR_PAD_LEFT);	// zero-padded numbers less than 10
		}
		natsort($numbers);
		return $numbers;
	}
	
	/**
	 * Replace Values in comment preview.
	 */
	public function PostController_AfterCommentPreviewFormat_Handler($Sender) {
		if (!C('Plugins.ProgressBars.FormatValues', TRUE))
			return;
		
		$Sender->Comment->Body = $this->DoValues($Sender->Comment->Body);
	}
	
	public function PostController_Render_Before($Sender) {
		$this->_ProgressBarsSetup($Sender);
	}
	
	public static function DoValues($Text) {
		$Text = ' '.$Text.' ';

		$LowerSplit = C('Plugins.ProgressBars.LowerSplit');
		if (!$LowerSplit) $LowerSplit = 10;

		$UpperSplit = C('Plugins.ProgressBars.UpperSplit');
		if (!$UpperSplit) $UpperSplit = 80;

		$Greyscale = C('Plugins.ProgressBars.Greyscale');

		$Values = ProgressBarsPlugin::GetValues();
		foreach ($Values as $Replacement) {
			if (strpos($Text, $Replacement) !== FALSE)
				if (intval($Replacement) == 100) {
					$Colour = 'black';
					if ($Greyscale) $Colour = 'bgrey';
				} else if (intval($Replacement) < $LowerSplit) {
					$Colour = 'red';
					if ($Greyscale) $Colour = 'lgrey';
				} else if (intval($Replacement) > $UpperSplit) {
					$Colour = 'green';
					if ($Greyscale) $Colour = 'dgrey';
				} else {
					$Colour = 'yellow';
					if ($Greyscale) $Colour = 'mgrey';
				}

				$Text = preg_replace(
					"#\[progress\s" . $Replacement . "\%*\]#m",
					$Replacement . '%<br><div class="progressbar pb' . $Replacement . ' ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $Replacement . '"><div class="ui-progressbar-value ui-widget-header ui-corner-left ' . $Colour . '" style="width: ' . $Replacement . '%; "></div></div>',
					$Text
				);
		}

		return substr($Text, 1, -1);
	}

	/**
	 * Prepare page
	 */
	private function _ProgressBarsSetup($Sender) {
		$Sender->AddCssFile('progressbars.css', 'plugins/ProgressBars');
	}
	
	public function Setup() {
		return TRUE;
	}
	
}
?>