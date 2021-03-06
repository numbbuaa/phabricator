<?php

/**
 * A collection of dashboard panels with a specific layout.
 */
final class PhabricatorDashboard extends PhabricatorDashboardDAO
  implements PhabricatorPolicyInterface {

  protected $name;
  protected $viewPolicy;
  protected $editPolicy;

  private $panelPHIDs = self::ATTACHABLE;
  private $panels = self::ATTACHABLE;

  public static function initializeNewDashboard(PhabricatorUser $actor) {
    return id(new PhabricatorDashboard())
      ->setName('')
      ->setViewPolicy(PhabricatorPolicies::POLICY_USER)
      ->setEditPolicy($actor->getPHID());
  }

  public function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorDashboardPHIDTypeDashboard::TYPECONST);
  }

  public function attachPanelPHIDs(array $phids) {
    $this->panelPHIDs = $phids;
    return $this;
  }

  public function getPanelPHIDs() {
    return $this->assertAttached($this->panelPHIDs);
  }

  public function attachPanels(array $panels) {
    assert_instances_of($panels, 'PhabricatorDashboardPanel');
    $this->panels = $panels;
    return $this;
  }

  public function getPanels() {
    return $this->assertAttached($this->panels);
  }


/* -(  PhabricatorPolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhabricatorPolicyCapability::CAN_VIEW,
      PhabricatorPolicyCapability::CAN_EDIT,
    );
  }

  public function getPolicy($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return $this->getViewPolicy();
      case PhabricatorPolicyCapability::CAN_EDIT:
        return $this->getEditPolicy();
    }
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return false;
  }

  public function describeAutomaticCapability($capability) {
    return null;
  }

}
