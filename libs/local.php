<?php

declare(strict_types=1);

trait WeathermanLocalLib
{
    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    public static $WEATHERMAN_MODULE_NONE = 0;
    public static $WEATHERMAN_MODULE_CLASSIC = 1;
    public static $WEATHERMAN_MODULE_EDITION = 2;

    public static $PRECIPITATION_DRY = 0;
    public static $PRECIPITATION_DRIZZLE = 1;
    public static $PRECIPITATION_MIST = 2;
    public static $PRECIPITATION_LIGHT = 3;
    public static $PRECIPITATION_MODERATE = 4;
    public static $PRECIPITATION_HEAVY = 5;
    public static $PRECIPITATION_SHOWERS = 6;
    public static $PRECIPITATION_STORM = 7;
    public static $PRECIPITATION_DOWNPOUR = 8;

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1],
            ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000],
        ];
        $this->CreateVarProfile('Weatherman.RainDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);
        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1],
            ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xFFFF99],
        ];
        $this->CreateVarProfile('Weatherman.SunDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $this->CreateVarProfile('Weatherman.Azimut', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Elevation', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('Weatherman.hour', VARIABLETYPE_INTEGER, ' h', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $this->CreateVarProfile('Weatherman.min', VARIABLETYPE_INTEGER, ' m', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $associations = [
            ['Wert' =>  self::$PRECIPITATION_DRY, 'Name' => $this->Translate('dry'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_DRIZZLE, 'Name' => $this->Translate('drizzle'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_MIST, 'Name' => $this->Translate('mist'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_LIGHT, 'Name' => $this->Translate('light rain'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_MODERATE, 'Name' => $this->Translate('moderate rain'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_HEAVY, 'Name' => $this->Translate('heavy rain'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_SHOWERS, 'Name' => $this->Translate('showers'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_STORM, 'Name' => $this->Translate('rain storm'), 'Farbe' => -1],
            ['Wert' => self::$PRECIPITATION_DOWNPOUR, 'Name' => $this->Translate('downpour'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('Weatherman.PrecipitationLevel', VARIABLETYPE_INTEGER, '', 0, 8, 0, 1, 'Rainfall', $associations, $reInstall);
        $this->CreateVarProfile('Weatherman.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity', [], $reInstall);
        $this->CreateVarProfile('Weatherman.WindAngle', VARIABLETYPE_INTEGER, ' °', 0, 360, 0, 0, 'WindDirection', [], $reInstall);
        $this->CreateVarProfile('Weatherman.WindStrength', VARIABLETYPE_INTEGER, ' bft', 0, 13, 0, 0, 'WindSpeed', [], $reInstall);

        $this->CreateVarProfile('Weatherman.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Lux', VARIABLETYPE_FLOAT, ' lx', 0, 0, 0, 0, 'Sun', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Precipitation', VARIABLETYPE_FLOAT, ' mm/h', 0, 60, 0, 1, 'Rainfall', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Pressure', VARIABLETYPE_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Rainfall', VARIABLETYPE_FLOAT, ' mm', 0, 60, 0, 1, 'Rainfall', [], $reInstall);
        $this->CreateVarProfile('Weatherman.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature', [], $reInstall);
        $associations = [
            ['Wert' =>  0, 'Name' => '%.1f', 'Farbe' => 0x80FF00],
            ['Wert' => 3, 'Name' => '%.1f', 'Farbe' => 0xFFFF00],
            ['Wert' => 6, 'Name' => '%.1f', 'Farbe' => 0xFF8040],
            ['Wert' => 8, 'Name' => '%.1f', 'Farbe' => 0xFF0000],
            ['Wert' => 11, 'Name' => '%.1f', 'Farbe' => 0xFF00FF],
        ];
        $this->CreateVarProfile('Weatherman.UV-Index', VARIABLETYPE_FLOAT, '', 0, 12, 0, 1, 'Sun', $associations, $reInstall);
        $this->CreateVarProfile('Weatherman.Windchill', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('Weatherman.WindSpeed', VARIABLETYPE_FLOAT, ' km/h', 0, 100, 0, 0, 'WindSpeed', [], $reInstall);

        $this->CreateVarProfile('Weatherman.WindDirection', VARIABLETYPE_STRING, '', 0, 0, 0, 0, 'WindDirection', [], $reInstall);
    }
}
