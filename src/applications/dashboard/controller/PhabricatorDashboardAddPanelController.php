<?php

final class PhabricatorDashboardAddPanelController
  extends PhabricatorDashboardController {

  private $id;

  public function willProcessRequest(array $data) {
    $this->id = idx($data, 'id');
  }

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $dashboard = id(new PhabricatorDashboardQuery())
      ->setViewer($viewer)
      ->withIDs(array($this->id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$dashboard) {
      return new Aphront404Response();
    }

    $dashboard_uri = $this->getApplicationURI('view/'.$dashboard->getID().'/');

    $v_panel = $request->getStr('panel');
    $e_panel = true;
    $errors = array();
    if ($request->isFormPost()) {
      if (strlen($v_panel)) {
        $panel = id(new PhabricatorObjectQuery())
          ->setViewer($viewer)
          ->withNames(array($v_panel))
          ->withTypes(array(PhabricatorDashboardPHIDTypePanel::TYPECONST))
          ->executeOne();
        if (!$panel) {
          $errors[] = pht('No such panel!');
          $e_panel = pht('Invalid');
        }
      } else {
        $errors[] = pht('Name a panel to add.');
        $e_panel = pht('Required');
      }

      if (!$errors) {
        $xactions = array();
        $xactions[] = id(new PhabricatorDashboardTransaction())
          ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
          ->setMetadataValue(
            'edge:type',
            PhabricatorEdgeConfig::TYPE_DASHBOARD_HAS_PANEL)
          ->setNewValue(
            array(
              '+' => array(
                $panel->getPHID() => $panel->getPHID(),
              ),
            ));

        $editor = id(new PhabricatorDashboardTransactionEditor())
          ->setActor($viewer)
          ->setContentSourceFromRequest($request)
          ->setContinueOnMissingFields(true)
          ->setContinueOnNoEffect(true)
          ->applyTransactions($dashboard, $xactions);

        return id(new AphrontRedirectResponse())->setURI($dashboard_uri);
      }
    }

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendRemarkupInstructions(
        pht('Enter a panel monogram like `W123`.'))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setName('panel')
          ->setLabel(pht('Panel'))
          ->setValue($v_panel)
          ->setError($e_panel));

    return $this->newDialog()
      ->setTitle(pht('Add Panel'))
      ->setErrors($errors)
      ->appendChild($form->buildLayoutView())
      ->addCancelButton($dashboard_uri)
      ->addSubmitButton(pht('Add Panel'));
  }

}
