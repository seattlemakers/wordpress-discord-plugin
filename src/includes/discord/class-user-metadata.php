<?php

namespace SeattleMakers\Discord;

class User_Metadata
{
    public $member = false;
    public $staff = false;
    public $maketeer = false;

    public function to_list(): array
    {
        $list = [];
        if ($this->member) {
            $list[] = "Member";
        }
        if ($this->staff) {
            $list[] = "Staff";
        }
        if ($this->maketeer) {
            $list[] = "Maketeer";
        }

        return $list;
    }
}