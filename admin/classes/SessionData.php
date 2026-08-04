<?php

class SessionData
{
    public static function getPermission($id = null)
    {
        return true;
    }

    public static function getUserType()
    {
        return $_SESSION['panel_user']['rol'] ?? null;
    }

    public static function getSecretaria()
    {
        return null;
    }

    public static function getCodigoMunicipio()
    {
        return null;
    }

    public static function getUserFullName()
    {
        return $_SESSION['panel_user']['nombre_completo'] ?? null;
    }
}