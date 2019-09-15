<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class Weatherman extends IPSModule
{
    use WeathermanCommon;

    public function Create()
    {
        parent::Create();

        $this->RequireParent('{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 1;

        $this->SetStatus(IS_ACTIVE);
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    protected function GetFormElements()
    {
        $formElements = [];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Module description',
                            'onClick' => 'echo "https://github.com/demel42/IPSymconWeatherman/blob/master/README.md";'
                        ];

        return $formActions;
    }

    public function ReceiveData($msg)
    {
        $jmsg = json_decode($msg, true);
        $data = utf8_decode($jmsg['Buffer']);

        $this->SendDebug(__FUNCTION__, 'data=' . $data, 0);

        $rdata = $this->GetMultiBuffer('Data');
        if (substr($data, -1) == chr(4)) {
            $ndata = $rdata . substr($data, 0, -1);
            $jdata = json_decode($ndata, true);
            if ($jdata == '') {
                $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg() . ', data=' . $ndata, 0);
            } else {
                $this->ProcessData($jdata);
            }
            $ndata = '';
        } else {
            $ndata = $rdata . $data;
        }
        $this->SetMultiBuffer('Data', $ndata);
    }

    private function ProcessData($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);
    }
}
