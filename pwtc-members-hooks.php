<?php

function pwtc_members_strip_phone_number($phoneNumber) {
    $phoneNumber = preg_replace('/[^0-9]/','',$phoneNumber);
    return $phoneNumber;
}

function pwtc_members_format_phone_number($phoneNumber) {
    $phoneNumber = pwtc_members_strip_phone_number($phoneNumber);
    if (strlen($phoneNumber) > 10) {
        $countryCode = substr($phoneNumber, 0, strlen($phoneNumber)-10);
        $areaCode = substr($phoneNumber, -10, 3);
        $nextThree = substr($phoneNumber, -7, 3);
        $lastFour = substr($phoneNumber, -4, 4);
        $phoneNumber = '+'.$countryCode.' ('.$areaCode.') '.$nextThree.'-'.$lastFour;
    }
    else if (strlen($phoneNumber) == 10) {
        $areaCode = substr($phoneNumber, 0, 3);
        $nextThree = substr($phoneNumber, 3, 3);
        $lastFour = substr($phoneNumber, 6, 4);
        $phoneNumber = '('.$areaCode.') '.$nextThree.'-'.$lastFour;
    }
    else if (strlen($phoneNumber) == 7) {
        $nextThree = substr($phoneNumber, 0, 3);
        $lastFour = substr($phoneNumber, 3, 4);
        $phoneNumber = $nextThree.'-'.$lastFour;
    }
    return $phoneNumber;
}

function pwtc_members_is_expired($membership) {
    $is_expired = false;
    $team = false;
    if (function_exists('wc_memberships_for_teams_get_user_membership_team')) {
        $team = wc_memberships_for_teams_get_user_membership_team($membership->get_id());
    }
    if ($team) {
        if ($team->is_membership_expired()) {
            $is_expired = true;
        }
    }
    else {
        if ($membership->is_expired()) {
            $is_expired = true;
        }
    }
    return $is_expired;
}