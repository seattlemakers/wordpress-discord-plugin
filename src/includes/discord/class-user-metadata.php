<?php

namespace SeattleMakers\Discord;

class User_Metadata
{
    public $member = false;
    public $staff = false;
    public $maketeer = false;

    public const SCHEMA = [
        [
            'key' => 'member',
            'name' => 'Member',
            'description' => 'Is an active member',
            'type' => 7
        ],
        [
            'key' => 'maketeer',
            'name' => 'Maketeer',
            'description' => 'Is an active maketeer',
            'type' => 7
        ],
        [
            'key' => 'staff',
            'name' => 'Staff',
            'description' => 'Is a member of staff',
            'type' => 7
        ],
    ];

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