<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class Weatherman extends IPSModule
{
    use WeathermanCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('use_fields', '');

        $this->RegisterPropertyBoolean('windspeed_in_kmh', false);

        $this->CreateVarProfile('Weatherman.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity');

        $this->CreateVarProfile('Weatherman.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('Weatherman.min', VARIABLETYPE_INTEGER, ' m', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('Weatherman.hour', VARIABLETYPE_INTEGER, ' h', 0, 0, 0, 0, 'Clock');

        $this->CreateVarProfile('Weatherman.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Weatherman.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
        $this->CreateVarProfile('Weatherman.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Weatherman.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge');
        $this->CreateVarProfile('Weatherman.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Weatherman.Windchill', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Weatherman.Pressure', VARIABLETYPE_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge');
        $this->CreateVarProfile('Weatherman.WindSpeed', VARIABLETYPE_FLOAT, ' km/h', 0, 100, 0, 0, 'WindSpeed'); // m/s
        $this->CreateVarProfile('Weatherman.WindStrength', VARIABLETYPE_INTEGER, ' bft', 0, 13, 0, 0, 'WindSpeed');
        $this->CreateVarProfile('Weatherman.WindAngle', VARIABLETYPE_INTEGER, ' °', 0, 360, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Weatherman.WindDirection', VARIABLETYPE_STRING, '', 0, 0, 0, 0, 'WindDirection');
        $this->CreateVarProfile('Weatherman.Rainfall', VARIABLETYPE_FLOAT, ' mm', 0, 60, 0, 1, 'Rainfall');
        $this->CreateVarProfile('Weatherman.RainStrength', VARIABLETYPE_FLOAT, ' mm/h', 0, 60, 0, 1, 'Rainfall');
        $this->CreateVarProfile('Weatherman.Lux', VARIABLETYPE_FLOAT, ' lx', 0, 0, 0, 0, 'Sun');
        $this->CreateVarProfile('Weatherman.Azimut', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '');
        $this->CreateVarProfile('Weatherman.Elevation', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '');

        $associations = [];
        $associations[] = ['Wert' =>   0, 'Name' => '%.1f', 'Farbe' => 0x80FF00];
        $associations[] = ['Wert' =>   3, 'Name' => '%.1f', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' =>   6, 'Name' => '%.1f', 'Farbe' => 0xFF8040];
        $associations[] = ['Wert' =>   8, 'Name' => '%.1f', 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' =>  11, 'Name' => '%.1f', 'Farbe' => 0xFF00FF];
        $this->CreateVarProfile('Weatherman.UV-Index', VARIABLETYPE_FLOAT, '', 0, 12, 0, 1, 'Sun', $associations);

        $this->RequireParent('{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $vpos = 1;

        $fieldMap = $this->getFieldMap();
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $use = false;
            $found = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = $this->GetArrayElem($field, 'use', false);
                    if ($use == '') {
                        $use = false;
                    }
                    break;
                }
            }
            $desc = $this->GetArrayElem($map, 'desc', '');
            $vartype = $this->GetArrayElem($map, 'type', '');
            $varprof = $this->GetArrayElem($map, 'prof', '');
            $this->SendDebug(__FUNCTION__, 'register variable: ident=' . $ident . ', vartype=' . $vartype . ', varprof=' . $varprof . ', use=' . $this->bool2str($use), 0);
            $this->MaintainVariable($ident, $this->Translate($desc), $vartype, $varprof, $vpos++, $use);
        }

        $vpos = 100;

        $this->MaintainVariable('LastMeasurement', $this->Translate('Last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('Uptime', $this->Translate('Uptime'), VARIABLETYPE_INTEGER, 'Weatherman.sec', $vpos++, true);
        $this->MaintainVariable('WifiStrength', $this->Translate('wifi-signal'), VARIABLETYPE_INTEGER, 'Weatherman.Wifi', $vpos++, true);

        $windspeed_in_kmh = $this->ReadPropertyBoolean('windspeed_in_kmh');
        if (IPS_VariableProfileExists('Weatherman.WindSpeed')) {
            IPS_SetVariableProfileText('Weatherman.WindSpeed', '', ($windspeed_in_kmh ? ' km/h' : ' m/s'));
        }

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
        $formElements[] = ['type' => 'Label', 'label' => 'Weatherman'];

        $values = [];
        $fieldMap = $this->getFieldMap();
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $desc = $this->GetArrayElem($map, 'desc', '');
            $values[] = ['ident' => $ident, 'desc' => $this->Translate($desc)];
        }

        $columns = [];
        $columns[] = [
                        'caption' => 'Name',
                        'name'    => 'ident',
                        'width'   => '250px',
                        'save'    => true
                    ];
        $columns[] = [
                        'caption' => 'Description',
                        'name'    => 'desc',
                        'width'   => 'auto'
                    ];
        $columns[] = [
                        'caption' => 'use',
                        'name'    => 'use',
                        'width'   => '150px',
                        'edit'    => [
                                'caption' => 'use Variable', 'type' => 'CheckBox', 'name' => 'ident'
                            ]
                    ];

        $formElements[] = [
                    'type'     => 'List',
                    'name'     => 'use_fields',
                    'caption'  => 'available variables',
                    'rowCount' => count($values),
                    'add'      => false,
                    'delete'   => false,
                    'columns'  => $columns,
                    'values'   => $values
                ];

        $formElements[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'windspeed_in_kmh',
                    'caption' => 'Windspeed in km/h instead of m/s'
                ];

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

        $modultyp = $this->GetArrayElem($jdata, 'modultyp', '');

        $systeminfo = $this->GetArrayElem($jdata, 'Systeminfo', '');
        $this->SendDebug(__FUNCTION__, 'Systeminfo=' . print_r($systeminfo, true), 0);

        $s = $this->GetArrayElem($jdata, 'Systeminfo.zeitpunkt', '');
        if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+) /([0-9]+)h([0-9]+)$#', $s, $r)) {
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

        $fieldMap = $this->getFieldMap();
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $vars = $this->GetArrayElem($jdata, 'vars', '');
        foreach ($vars as $var) {
            $ident = $this->GetArrayElem($var, 'homematic_name', '');
            $value = $this->GetArrayElem($var, 'value', '');

            $vartype = VARIABLETYPE_STRING;
            $varprof = '';
            foreach ($fieldMap as $map) {
                if ($ident == $this->GetArrayElem($map, 'ident', '')) {
                    $vartype = $this->GetArrayElem($map, 'type', '');
                    $varprof = $this->GetArrayElem($map, 'prof', '');
                    break;
                }
            }

            $found = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = $this->GetArrayElem($field, 'use', false);
                    if ($use == '') {
                        $use = false;
                    }
                    if (!$use) {
                        continue;
                    }

                    if ($varprof == 'Weatherman.WindSpeed' && $windspeed_in_kmh) {
                        $value = floatval($value) * 3.6;
                    }

                    switch ($vartype) {
                        case VARIABLETYPE_INTEGER:
                            $this->SetValue($ident, intval($value));
                            break;
                        default:
                            $this->SetValue($ident, $value);
                            break;
                    }
                    $found = true;
                }
            }
            if (!$found) {
                $this->SendDebug(__FUNCTION__, '.. ignore ident=' . $ident . ', value=' . $value, 0);
            }
        }

        $this->SetValue('LastUpdate', time());
    }

    private function getFieldMap()
    {
        $map = [
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
                ],
                [
                    'ident'  => 'w_regenstaerke',
                    'desc'   => 'Rainfall',
                    'type'   => VARIABLETYPE_FLOAT,
                    'prof'   => 'Weatherman.RainStrength',
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

        return $map;
    }
}
