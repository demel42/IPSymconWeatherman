<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class Weatherman extends IPSModule
{
    use Weatherman\StubsCommonLib;
    use WeathermanLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonConstruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('module_type', self::$WEATHERMAN_MODULE_CLASSIC);
        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyBoolean('windspeed_in_kmh', false);

        $this->RegisterPropertyInteger('altitude', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);
        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_windstrength_text', false);
        $this->RegisterPropertyBoolean('with_precipitation_level', false);
        $this->RegisterPropertyInteger('regensensor_niesel', 0);

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RequireParent('{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}');
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $varList = [];

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $module_type = $this->ReadPropertyInteger('module_type');
        $fieldMap = $this->getFieldMap($module_type);
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            if ($use) {
                $varList[] = $ident;
            }
        }

        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        if ($with_heatindex) {
            if (!(in_array('w_temperatur', $varList) && in_array('w_feuchte_rel', $varList))) {
                $this->SendDebug(__FUNCTION__, '"with_heatindex" needs "w_temperatur", "w_feuchte_rel"', 0);
                $r[] = $this->Translate('Heatindex needs "w_temperatur", "w_feuchte_rel"');
            }
        }

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        if ($with_absolute_pressure) {
            $altitude = $this->ReadPropertyInteger('altitude');
            if (!(in_array('w_barometer', $varList) && in_array('w_temperatur', $varList) && $altitude > 0)) {
                $this->SendDebug(__FUNCTION__, '"with_absolute_pressure" needs "w_barometer", "w_temperatur" and "altitude"', 0);
                $r[] = $this->Translate('Absolute pressure needs "w_barometer", "w_temperatur" and the altitude');
            }
        }

        $with_windstrength_text = $this->ReadPropertyBoolean('with_windstrength_text');
        if ($with_windstrength_text) {
            if (!(in_array('w_windstaerke', $varList))) {
                $this->SendDebug(__FUNCTION__, '"with_windstrength_text" needs "w_windstaerke"', 0);
                $r[] = $this->Translate('Windstrength as text needs "w_windstaerke"');
            }
        }

        $with_precipitation_level = $this->ReadPropertyBoolean('with_precipitation_level');
        if ($with_precipitation_level) {
            if (!(in_array('w_regen_letzte_h', $varList))) {
                $this->SendDebug(__FUNCTION__, '"with_precipitation_level" needs "w_regen_letzte_h"', 0);
                $r[] = $this->Translate('Precipitation level needs "w_regen_letzte_h"');
            }
            $regensensor_niesel = $this->ReadPropertyInteger('regensensor_niesel');
            if ($regensensor_niesel > 0) {
                if (!(in_array('w_regensensor_wert', $varList))) {
                    $this->SendDebug(__FUNCTION__, '"regensensor_niesel" needs "w_regensensor_wert"', 0);
                    $r[] = $this->Translate('use rainsensor to detect drizzle needs "w_regensensor_wert"');
                }
            }
        }

        return $r;
    }

    private function CheckModuleUpdate(array $oldInfo, array $newInfo)
    {
        $r = [];

        if ($this->version2num($oldInfo) < $this->version2num('1.14')) {
            $r[] = $this->Translate('Adjusting the value range of various variable profiles');
        }

        return $r;
    }

    private function CompleteModuleUpdate(array $oldInfo, array $newInfo)
    {
        if ($this->version2num($oldInfo) < $this->version2num('1.14')) {
            $vps = [
                'Weatherman.Temperatur',
                'Weatherman.Humidity',
                'Weatherman.absHumidity',
                'Weatherman.Dewpoint',
                'Weatherman.Heatindex',
                'Weatherman.WindSpeed',
                'Weatherman.Windchill',
            ];
            foreach ($vps as $vp) {
                if (IPS_VariableProfileExists($vp)) {
                    IPS_DeleteVariableProfile($vp);
                }
            }
            $this->InstallVarProfiles(false);
        }

        return '';
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $vpos = 1;

        $varList = [];

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $module_type = $this->ReadPropertyInteger('module_type');
        $fieldMap = $this->getFieldMap($module_type);

        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            if ($use) {
                $varList[] = $ident;
            }
            $desc = $this->GetArrayElem($map, 'desc', '');
            $vartype = $this->GetArrayElem($map, 'type', '');
            $varprof = $this->GetArrayElem($map, 'prof', '');
            $this->SendDebug(__FUNCTION__, 'register variable: ident=' . $ident . ', vartype=' . $vartype . ', varprof=' . $varprof . ', use=' . $this->bool2str($use), 0);
            $this->MaintainVariable($ident, $this->Translate($desc), $vartype, $varprof, $vpos++, $use);
        }

        $vpos = 80;

        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Weatherman.Heatindex', $vpos++, $with_heatindex);

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $this->MaintainVariable('AbsolutePressure', $this->Translate('Absolute pressure'), VARIABLETYPE_FLOAT, 'Weatherman.Pressure', $vpos++, $with_absolute_pressure);

        $with_windstrength_text = $this->ReadPropertyBoolean('with_windstrength_text');
        $this->MaintainVariable('WindStrengthText', $this->Translate('Windstrength'), VARIABLETYPE_STRING, '', $vpos++, $with_windstrength_text);

        $with_precipitation_level = $this->ReadPropertyBoolean('with_precipitation_level');
        $this->MaintainVariable('PrecipitationLevel', $this->Translate('Precipitation level'), VARIABLETYPE_INTEGER, 'Weatherman.PrecipitationLevel', $vpos++, $with_precipitation_level);

        $vpos = 100;

        $this->MaintainVariable('LastMeasurement', $this->Translate('Last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('Uptime', $this->Translate('Uptime'), VARIABLETYPE_INTEGER, 'Weatherman.sec', $vpos++, true);
        $this->MaintainVariable('WifiStrength', $this->Translate('wifi-signal'), VARIABLETYPE_INTEGER, 'Weatherman.Wifi', $vpos++, true);

        $windspeed_in_kmh = $this->ReadPropertyBoolean('windspeed_in_kmh');
        if (IPS_VariableProfileExists('Weatherman.WindSpeed')) {
            IPS_SetVariableProfileText('Weatherman.WindSpeed', '', ($windspeed_in_kmh ? ' km/h' : ' m/s'));
        }

        $objList = [];
        $this->findVariables($this->InstanceID, $objList);
        foreach ($objList as $obj) {
            $ident = $obj['ObjectIdent'];
            if (!in_array($ident, $varList)) {
                $this->SendDebug(__FUNCTION__, 'unregister variable: ident=' . $ident, 0);
                $this->UnregisterVariable($ident);
            }
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function findVariables($objID, &$objList)
    {
        $chldIDs = IPS_GetChildrenIDs($objID);
        foreach ($chldIDs as $chldID) {
            $obj = IPS_GetObject($chldID);
            switch ($obj['ObjectType']) {
                case OBJECTTYPE_VARIABLE:
                    if (preg_match('#^w_#', $obj['ObjectIdent'], $r)) {
                        $objList[] = $obj;
                    }
                    break;
                case OBJECTTYPE_CATEGORY:
                    $this->findVariables($chldID, $objList);
                    break;
                default:
                    break;
            }
        }
    }

    private function UpdateUseFields(int $module_type, object $use_fields)
    {
        $values = [];

        $fieldMap = $this->getFieldMap($module_type);
        foreach ($fieldMap as $map) {
            $ident = $map['ident'];
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $values[] = [
                'ident' => $ident,
                'desc'  => $this->Translate($map['desc']),
                'use'   => $use
            ];
        }

        $this->UpdateFormField('use_fields', 'values', json_encode($values));
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Weatherman');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'     => 'Select',
            'name'     => 'module_type',
            'caption'  => 'Module type',
            'options'  => [
                [
                    'caption' => $this->Translate('none'),
                    'value'   => self::$WEATHERMAN_MODULE_NONE,
                ],
                [
                    'caption' => $this->Translate('Classic'),
                    'value'   => self::$WEATHERMAN_MODULE_CLASSIC,
                ],
                [
                    'caption' => $this->Translate('Edition'),
                    'value'   => self::$WEATHERMAN_MODULE_EDITION,
                ],
                [
                    'caption' => $this->Translate('Edition 2.1'),
                    'value'   => self::$WEATHERMAN_MODULE_EDITION21,
                ],
            ],
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateUseFields", "");',
        ];

        $values = [];
        $module_type = $this->ReadPropertyInteger('module_type');
        $fieldMap = $this->getFieldMap($module_type);
        foreach ($fieldMap as $map) {
            $values[] = [
                'ident' => $map['ident'],
                'desc'  => $this->Translate($map['desc']),
            ];
        }

        $formElements[] = [
            'type'  => 'ExpansionPanel',
            'items' => [
                [
                    'type'     => 'List',
                    'name'     => 'use_fields',
                    'caption'  => 'available variables',
                    'rowCount' => count($values),
                    'add'      => false,
                    'delete'   => false,
                    'columns'  => [
                        [
                            'name'    => 'ident',
                            'width'   => '200px',
                            'save'    => true,
                            'caption' => 'Name',
                        ],
                        [
                            'name'    => 'desc',
                            'width'   => 'auto',
                            'caption' => 'Description',
                        ],
                        [
                            'name'    => 'use',
                            'width'   => '100px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ],
                            'caption' => 'use',
                        ],
                    ],
                    'values'   => $values,
                ],
            ],
            'caption'  => 'Variables',
        ];

        $formElements[] = [
            'type'  => 'ExpansionPanel',
            'items' => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'windspeed_in_kmh',
                    'caption' => 'Windspeed in km/h instead of m/s'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'altitude',
                    'caption' => 'Station altitude'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'additional Calculations'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_heatindex',
                    'caption' => ' ... Heatindex (needs "w_temperatur", "w_feuchte_rel")'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_absolute_pressure',
                    'caption' => ' ... absolute pressure (needs "w_barometer", "w_temperatur" and the altitude)'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_windstrength_text',
                    'caption' => ' ... Windstrength as text (needs "w_windstaerke")'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_precipitation_level',
                    'caption' => ' ... Precipitation level (needs "w_regen_letzte_h")'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' ... use rainsensor to detect drizzle (needs "w_regensensor_wert")',
                ],
                [
                    'type'    => 'NumberSpinner',
                    'minumum' => 0,
                    'maximum' => 100,
                    'name'    => 'regensensor_niesel',
                    'caption' => 'minumum rainsensor-value',
                ],
            ],
            'caption' => 'Options',
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            case 'UpdateUseFields':
                $this->UpdateUseFields();
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    public function ReceiveData($msg)
    {
        $jmsg = json_decode($msg, true);
        $data = $jmsg['Buffer'];

        switch ((int) $jmsg['Type']) {
            case 0: /* Data */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => received: ' . $data, 0);
                $rdata = $this->GetMultiBuffer('Data');
                if (substr($data, -1) == chr(4)) {
                    $ndata = $rdata . substr($data, 0, -1);
                } else {
                    $ndata = $rdata . $data;
                }
                break;
            case 1: /* Connected */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => connected', 0);
                $ndata = '';
                break;
            case 2: /* Disconnected */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => disonnected', 0);
                $rdata = $this->GetMultiBuffer('Data');
                if ($rdata != '') {
                    $jdata = json_decode($rdata, true);
                    if ($jdata == '') {
                        $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg() . ', data=' . $rdata, 0);
                    } else {
                        $this->ProcessData($jdata);
                    }
                }
                $ndata = '';
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown Type, jmsg=' . print_r($jmsg, true), 0);
                break;
        }
        $this->SetMultiBuffer('Data', $ndata);
    }

    private function ProcessData($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);

        $modultyp = $this->GetArrayElem($jdata, 'modultyp', '');

        $systeminfo = $this->GetArrayElem($jdata, 'Systeminfo', '');
        $this->SendDebug(__FUNCTION__, 'Systeminfo=' . print_r($systeminfo, true), 0);

        $s = $this->GetArrayElem($jdata, 'Systeminfo.zeitpunkt', '');
        if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)[ ]*/([0-9]+)h([0-9]+)$#', $s, $r)) {
            $tstamp = strtotime($r[1] . '-' . $r[2] . '-' . $r[3] . ' ' . $r[4] . ':' . $r[5] . ':00');
        } else {
            $this->SendDebug(__FUNCTION__, 'unable to decode date "' . $s . '"', 0);
            $tstamp = 0;
        }
        $this->SetValue('LastMeasurement', $tstamp);

        $uptime = $this->GetArrayElem($jdata, 'Systeminfo.sec_seit_reset', '');
        $this->SetValue('Uptime', $uptime);

        $rssi = $this->GetArrayElem($jdata, 'Systeminfo.WLAN_Signal_dBm', '');
        $this->SetValue('WifiStrength', $rssi);

        $this->SendDebug(__FUNCTION__, 'modultyp=' . $modultyp . ', measure=' . date('d.m.Y H:i:s', $tstamp) . ', rssi=' . $rssi . ', uptime=' . $uptime . 's', 0);

        $windspeed_in_kmh = $this->ReadPropertyBoolean('windspeed_in_kmh');

        $module_type = $this->ReadPropertyInteger('module_type');
        $fieldMap = $this->getFieldMap($module_type);
        $this->SendDebug(__FUNCTION__, 'fieldMap="' . print_r($fieldMap, true) . '"', 0);
        $identV = [];
        foreach ($fieldMap as $map) {
            $identV[] = $this->GetArrayElem($map, 'ident', '');
        }
        $identS = implode(',', $identV);
        $this->SendDebug(__FUNCTION__, 'known idents=' . $identS, 0);

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $use_fieldsV = [];
        foreach ($use_fields as $field) {
            if ((bool) $this->GetArrayElem($field, 'use', false)) {
                $use_fieldsV[] = $this->GetArrayElem($field, 'ident', '');
            }
        }
        $use_fieldsS = implode(',', $use_fieldsV);
        $this->SendDebug(__FUNCTION__, 'use fields=' . $use_fieldsS, 0);

        $vars = $this->GetArrayElem($jdata, 'vars', '');
        foreach ($vars as $var) {
            // $this->SendDebug(__FUNCTION__, 'var=' . print_r($var, true), 0);
            $ident = $this->GetArrayElem($var, 'homematic_name', '');
            // hotfix wegen umbenannter Variable
            if (in_array($ident, ['w_rtest', 'w_regen_stunden_heute'])) {
                $_ident = $ident;
                $ident = 'w_regenstunden_heute';
                $this->SendDebug(__FUNCTION__, 'use "' . $_ident . '" as "' . $ident . '"', 0);
            }
            $value = $this->GetArrayElem($var, 'value', '');
            $unit = $this->GetArrayElem($var, 'unit', '');

            $found = false;

            $vartype = VARIABLETYPE_STRING;
            $varprof = '';
            foreach ($fieldMap as $map) {
                if ($ident == $this->GetArrayElem($map, 'ident', '')) {
                    $found = true;
                    $vartype = $this->GetArrayElem($map, 'type', '');
                    $varprof = $this->GetArrayElem($map, 'prof', '');
                    break;
                }
            }

            if (!$found) {
                $this->SendDebug(__FUNCTION__, 'unknown ident "' . $ident . '", value=' . $value, 0);
                $this->LogMessage(__FUNCTION__ . ': unknown ident ' . $ident . ', value=' . $value, KL_NOTIFY);
                continue;
            }

            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    if (!$use) {
                        $this->SendDebug(__FUNCTION__, 'ignore ident "' . $ident . '", value=' . $value, 0);
                        continue;
                    }

                    $this->SendDebug(__FUNCTION__, 'use ident "' . $ident . '", value=' . $value, 0);

                    if ($varprof == 'Weatherman.WindSpeed') {
                        if ($unit == 'km/h') {
                            if (!$windspeed_in_kmh) {
                                $value = floatval($value) / 3.6;
                            }
                        } else {
                            if ($windspeed_in_kmh) {
                                $value = floatval($value) * 3.6;
                            }
                        }
                    }

                    if ($ident == 'w_barotrend') {
                        $value = str_replace('_', ' ', $value);
                    }

                    switch ($vartype) {
                        case VARIABLETYPE_INTEGER:
                            $this->SetValue($ident, intval($value));
                            break;
                        default:
                            $this->SetValue($ident, $value);
                            break;
                    }
                    break;
                }
            }
        }

        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        if ($with_heatindex) {
            $w_temperatur = $this->GetValue('w_temperatur');
            $w_feuchte_rel = $this->GetValue('w_feuchte_rel');
            $v = $this->calcHeatindex($w_temperatur, $w_feuchte_rel);
            $this->SetValue('Heatindex', $v);
        }

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        if ($with_absolute_pressure) {
            $w_barometer = $this->GetValue('w_barometer');
            $w_temperatur = $this->GetValue('w_temperatur');
            $altitude = $this->ReadPropertyInteger('altitude');
            $v = $this->calcAbsolutePressure($w_barometer, $w_temperatur, $altitude);
            $this->SetValue('AbsolutePressure', $v);
        }

        $with_windstrength_text = $this->ReadPropertyBoolean('with_windstrength_text');
        if ($with_windstrength_text) {
            $w_windstaerke = $this->GetValue('w_windstaerke');
            $v = $this->convertWindStrength2Text($w_windstaerke);
            $this->SetValue('WindStrengthText', $v);
        }

        $with_precipitation_level = $this->ReadPropertyBoolean('with_precipitation_level');
        if ($with_precipitation_level) {
            $w_regen_letzte_h = $this->GetValue('w_regen_letzte_h');
            $v = $this->convertPrecipitation2Level($w_regen_letzte_h);
            if ($v == 0) {
                $regensensor_niesel = $this->ReadPropertyInteger('regensensor_niesel');
                if ($regensensor_niesel > 0) {
                    $w_regensensor_wert = $this->GetValue('w_regensensor_wert');
                    if ($w_regensensor_wert > $regensensor_niesel) {
                        $v = 1; // Nieselregen
                    }
                }
            }
            $this->SetValue('PrecipitationLevel', $v);
        }

        $this->SetValue('LastUpdate', time());
    }

    private function getFieldMap(int $module_type)
    {
        $map_classic = [
            [
                'ident'  => 'w_ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'w_temperatur',
                'desc'   => 'Shadow temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'w_windchill',
                'desc'   => 'Windchill',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Windchill',
            ],
            [
                'ident'  => 'w_taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Dewpoint',
            ],
            [
                'ident'  => 'w_himmeltemperatur',
                'desc'   => 'Sky temperatur',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'w_feuchte_rel',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Humidity',
            ],
            [
                'ident'  => 'w_feuchte_abs',
                'desc'   => 'Absolute humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.absHumidity',
            ],
            [
                'ident'  => 'w_regensensor_wert',
                'desc'   => 'Rain sensor',
                'type'   => VARIABLETYPE_INTEGER,
            ],
            [
                'ident'  => 'w_regenmelder',
                'desc'   => 'Rain detector',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Weatherman.RainDetector',
            ],
            [
                'ident'  => 'w_regenstaerke',
                'desc'   => 'Precipitation',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Precipitation',
            ],
            [
                'ident'  => 'w_regen_letzte_h',
                'desc'   => 'Rainfall of last hour',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'w_regen_mm_heute',
                'desc'   => 'Rainfall of today',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'w_regenstunden_heute',
                'desc'   => 'Hours of rain today',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.hour',
            ],
            [
                'ident'  => 'w_regen_mm_gestern',
                'desc'   => 'Rainfall of yesterday',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'w_barometer',
                'desc'   => 'Air pressure',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Pressure',
            ],
            [
                'ident'  => 'w_barotrend',
                'desc'   => 'Trend of air pressure',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'w_wind_mittel',
                'desc'   => 'Windspeed',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.WindSpeed',
            ],
            [
                'ident'  => 'w_wind_spitze',
                'desc'   => 'Speed of gusts of last 10m',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.WindSpeed',
            ],
            [
                'ident'  => 'w_windstaerke',
                'desc'   => 'Windstrength',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.WindStrength',
            ],
            [
                'ident'  => 'w_windrichtung',
                'desc'   => 'Winddirection',
                'type'   => VARIABLETYPE_STRING,
                'prof'   => 'Weatherman.WindDirection',
            ],
            [
                'ident'  => 'w_wind_dir',
                'desc'   => 'Winddirection',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.WindAngle',
            ],
            [
                'ident'  => 'w_lux',
                'desc'   => 'Brightness',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Lux',
            ],
            [
                'ident'  => 'w_uv_index',
                'desc'   => 'UV-Index',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.UV-Index',
            ],
            [
                'ident'  => 'w_sonne_diff_temp',
                'desc'   => 'Difference between sun and shadow temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'w_sonnentemperatur',
                'desc'   => 'Sun temperatur',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'w_sonne_scheint',
                'desc'   => 'Sun detector',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Weatherman.SunDetector',
            ],
            [
                'ident'  => 'w_sonnenstunden_heute',
                'desc'   => 'Hours of sunshine today',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.hour',
            ],
            [
                'ident'  => 'w_elevation',
                'desc'   => 'Sun elevation',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.Elevation',
            ],
            [
                'ident'  => 'w_azimut',
                'desc'   => 'Sun azimut',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.Azimut',
            ],
            [
                'ident'  => 'w_minuten_vor_sa',
                'desc'   => 'Minutes from sunrise',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.min',
            ],
            [
                'ident'  => 'w_minuten_vor_su',
                'desc'   => 'Minutes from sunset',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.min',
            ],
        ];
        $map_edition = [
            [
                'ident'  => 'w_ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'w_temperatur',
                'desc'   => 'Shadow temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'w_windchill',
                'desc'   => 'Windchill',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Windchill',
            ],
            [
                'ident'  => 'w_taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Dewpoint',
            ],
            [
                'ident'  => 'w_feuchte_rel',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Humidity',
            ],
            [
                'ident'  => 'w_feuchte_abs',
                'desc'   => 'Absolute humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.absHumidity',
            ],
            [
                'ident'  => 'w_regensensor_wert',
                'desc'   => 'Rain sensor',
                'type'   => VARIABLETYPE_INTEGER,
            ],
            [
                'ident'  => 'w_regenmelder',
                'desc'   => 'Rain detector',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Weatherman.RainDetector',
            ],
            [
                'ident'  => 'w_regen_letzte_h',
                'desc'   => 'Rainfall of last hour',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'w_regen_mm_heute',
                'desc'   => 'Rainfall of today',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'w_regenstunden_heute',
                'desc'   => 'Hours of rain today',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.hour',
            ],
            [
                'ident'  => 'w_regen_mm_gestern',
                'desc'   => 'Rainfall of yesterday',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'w_barometer',
                'desc'   => 'Air pressure',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Pressure',
            ],
            [
                'ident'  => 'w_barotrend',
                'desc'   => 'Trend of air pressure',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'w_wind_1min',
                'desc'   => 'Speed of gusts of last minute',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.WindSpeed',
            ],
            [
                'ident'  => 'w_wind_10min',
                'desc'   => 'Speed of gusts of last 10 minutes',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.WindSpeed',
            ],
            [
                'ident'  => 'w_windstaerke',
                'desc'   => 'Windstrength',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.WindStrength',
            ],
            [
                'ident'  => 'w_windrichtung',
                'desc'   => 'Winddirection',
                'type'   => VARIABLETYPE_STRING,
                'prof'   => 'Weatherman.WindDirection',
            ],
            [
                'ident'  => 'w_wind_dir',
                'desc'   => 'Winddirection',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.WindAngle',
            ],
            [
                'ident'  => 'w_lux',
                'desc'   => 'Brightness',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Lux',
            ],
            [
                'ident'  => 'w_uv_index',
                'desc'   => 'UV-Index',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.UV-Index',
            ],
            [
                'ident'  => 'w_sonnentemperatur',
                'desc'   => 'Sun temperatur',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'w_sonne_scheint',
                'desc'   => 'Sun detector',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Weatherman.SunDetector',
            ],
            [
                'ident'  => 'w_sonnenstunden_heute',
                'desc'   => 'Hours of sunshine today',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.hour',
            ],
            [
                'ident'  => 'w_elevation',
                'desc'   => 'Sun elevation',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.Elevation',
            ],
            [
                'ident'  => 'w_azimut',
                'desc'   => 'Sun azimut',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.Azimut',
            ],
            [
                'ident'  => 'w_minuten_vor_sa',
                'desc'   => 'Minutes from sunrise',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.min',
            ],
            [
                'ident'  => 'w_minuten_vor_su',
                'desc'   => 'Minutes from sunset',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.min',
            ],
        ];
        $map_edition21 = [
            [
                'ident'  => 'wm_ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'wm_temperatur',
                'desc'   => 'Shadow temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'wm_windchill',
                'desc'   => 'Windchill',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Windchill',
            ],
            [
                'ident'  => 'wm_taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Dewpoint',
            ],
            [
                'ident'  => 'wm_himmeltemperatur',
                'desc'   => 'Sky temperatur',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'wm_feuchte_rel',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Humidity',
            ],
            [
                'ident'  => 'wm_feuchte_abs',
                'desc'   => 'Absolute humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.absHumidity',
            ],
            [
                'ident'  => 'wm_regensensor_wert',
                'desc'   => 'Rain sensor',
                'type'   => VARIABLETYPE_INTEGER,
            ],
            [
                'ident'  => 'wm_regenmelder',
                'desc'   => 'Rain detector',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Weatherman.RainDetector',
            ],
            [
                'ident'  => 'wm_regenstaerke',
                'desc'   => 'Precipitation',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Precipitation',
            ],
            [
                'ident'  => 'wm_regen_letzte_h',
                'desc'   => 'Rainfall of last hour',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'wm_regen_mm_heute',
                'desc'   => 'Rainfall of today',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'wm_regenstunden_heute',
                'desc'   => 'Hours of rain today',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.hour',
            ],
            [
                'ident'  => 'wm_regen_mm_gestern',
                'desc'   => 'Rainfall of yesterday',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Rainfall',
            ],
            [
                'ident'  => 'wm_barometer',
                'desc'   => 'Air pressure',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Pressure',
            ],
            [
                'ident'  => 'wm_barotrend',
                'desc'   => 'Trend of air pressure',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'wm_wind_mittel',
                'desc'   => 'Average wind speed',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.WindSpeed',
            ],
            [
                'ident'  => 'wm_wind_spitze',
                'desc'   => 'Maximum wind speed',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.WindSpeed',
            ],
            [
                'ident'  => 'wm_windstaerke',
                'desc'   => 'Windstrength',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.WindStrength',
            ],
            [
                'ident'  => 'wm_windrichtung',
                'desc'   => 'Winddirection',
                'type'   => VARIABLETYPE_STRING,
                'prof'   => 'Weatherman.WindDirection',
            ],
            [
                'ident'  => 'wm_wind_dir',
                'desc'   => 'Winddirection',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.WindAngle',
            ],
            [
                'ident'  => 'wm_lux',
                'desc'   => 'Brightness',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Lux',
            ],
            [
                'ident'  => 'wm_uv_index',
                'desc'   => 'UV-Index',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.UV-Index',
            ],
            [
                'ident'  => 'wm_sonnentemperatur',
                'desc'   => 'Sun temperatur',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Weatherman.Temperatur',
            ],
            [
                'ident'  => 'wm_sonne_scheint',
                'desc'   => 'Sun detector',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Weatherman.SunDetector',
            ],
            [
                'ident'  => 'wm_sonnenstunden_heute',
                'desc'   => 'Hours of sunshine today',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.hour',
            ],
            [
                'ident'  => 'wm_elevation',
                'desc'   => 'Sun elevation',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.Elevation',
            ],
            [
                'ident'  => 'wm_azimut',
                'desc'   => 'Sun azimut',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.Azimut',
            ],
            [
                'ident'  => 'wm_minuten_vor_sa',
                'desc'   => 'Minutes from sunrise',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.min',
            ],
            [
                'ident'  => 'wm_minuten_vor_su',
                'desc'   => 'Minutes from sunset',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Weatherman.min',
            ],
        ];

        switch ($module_type) {
            case self::$WEATHERMAN_MODULE_CLASSIC:
                $map = $map_classic;
                break;
            case self::$WEATHERMAN_MODULE_EDITION:
                $map = $map_edition;
                break;
            case self::$WEATHERMAN_MODULE_EDITION21:
                $map = $map_edition21;
                break;
            default:
                $map = [];
        }
        return $map;
    }

    // Luftdruck (Meereshöhe) in absoluten (lokaler) Luftdruck umrechnen
    //   Quelle: https://rechneronline.de/barometer/hoehe.php
    private function calcAbsolutePressure(float $pressure, float $temp, int $altitude)
    {
        // Temperaturgradient (geschätzt)
        $TG = 0.0065;

        // Höhe = Differenz Meereshöhe zu Standort
        $ad = $altitude * -1;

        // Temperatur auf Meereshöhe herunter rechnen
        //     Schätzung: Temperatur auf Meereshöhe = Temperatur + Temperaturgradient * Höhe
        $T = $temp + $TG * $ad;
        // Temperatur in Kelvin
        $TK = $T + 273.15;

        // Luftdruck auf Meereshöhe = Barometeranzeige / (1-Temperaturgradient*Höhe/Temperatur auf Meereshöhe in Kelvin)^(0,03416/Temperaturgradient)
        $AP = $pressure / pow((1 - $TG * $ad / $TK), (0.03416 / $TG));

        return $AP;
    }

    // Windstärke als Text ausgeben
    //  Quelle: https://de.wikipedia.org/wiki/Beaufortskala
    private function convertWindStrength2Text(int $bft)
    {
        $bft2txt = [
            'Calm',
            'Light air',
            'Light breeze',
            'Gentle breeze',
            'Moderate breeze',
            'Fresh breeze',
            'Strong breeze',
            'High wind',
            'Gale',
            'Strong gale',
            'Storm',
            'Hurricane force',
            'Violent storm'
        ];

        if ($bft >= 0 && $bft < count($bft2txt)) {
            $txt = $this->Translate($bft2txt[$bft]);
        } else {
            $txt = '';
        }
        return $txt;
    }

    // Temperatur als Heatindex umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Hitzeindex
    private function calcHeatindex(float $temp, float $hum)
    {
        if ($temp < 27 || $hum < 40) {
            return $temp;
        }
        $c1 = -8.784695;
        $c2 = 1.61139411;
        $c3 = 2.338549;
        $c4 = -0.14611605;
        $c5 = -1.2308094 * pow(10, -2);
        $c6 = -1.6424828 * pow(10, -2);
        $c7 = 2.211732 * pow(10, -3);
        $c8 = 7.2546 * pow(10, -4);
        $c9 = -3.582 * pow(10, -6);

        $hi = $c1
            + $c2 * $temp
            + $c3 * $hum
            + $c4 * $temp * $hum
            + $c5 * pow($temp, 2)
            + $c6 * pow($hum, 2)
            + $c7 * pow($temp, 2) * $hum
            + $c8 * $temp * pow($hum, 2)
            + $c9 * pow($temp, 2) * pow($hum, 2);
        $hi = round($hi); // ohne NK
        return $hi;
    }

    private function convertPrecipitation2Level(float $rain_1h)
    {
        $rain_map = [
            self::$PRECIPITATION_DRY      => 0,		// trocken
            self::$PRECIPITATION_DRIZZLE  => 0.01,	// Nieselregen
            self::$PRECIPITATION_MIST     => 0.1,	// Sprühregen
            self::$PRECIPITATION_LIGHT    => 0.4,	// leichter Regen
            self::$PRECIPITATION_MODERATE => 1.5,	// mäßiger Regen
            self::$PRECIPITATION_HEAVY    => 4,		// starker Regen
            self::$PRECIPITATION_SHOWERS  => 10,	// Schauerregen
            self::$PRECIPITATION_STORM    => 35,	// Gewitterregen
            self::$PRECIPITATION_DOWNPOUR => 100,	// Sturzregen
        ];

        $precipitation = 0;
        for ($i = count($rain_map) - 1; $i >= 0; $i--) {
            if ($rain_1h >= $rain_map[$i]) {
                $precipitation = $i;
                break;
            }
        }
        return $precipitation;
    }
}
