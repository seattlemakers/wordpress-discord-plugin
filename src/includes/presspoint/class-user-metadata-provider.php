<?php

namespace SeattleMakers\Presspoint;

// TODO: actually test this against presspoint install
class UserMetadataProvider
{
    const ROLE_TAXONOMY_ID = "ppu_roles_1506378218";
    const PAUPRESS_PLAN = 'pp_plan';
    const PP_ITEM_STATUS = "_pp_item_status";
    const ACTIVE = "active";
    const STAFF_ROLE = "Staff";
    const MAKETEER_ROLE = "Maketeer";

    public function get_metadata($user_id): \SeattleMakers\Discord\UserMetadata
    {
        $meta = new \SeattleMakers\Discord\UserMetadata();

        $roles_array = get_user_meta($user_id, self::ROLE_TAXONOMY_ID);
        foreach ($roles_array as $role) {
            $term = get_term($role, self::ROLE_TAXONOMY_ID);
            if ($term) {
                switch ($term->name) {
                    case self::STAFF_ROLE:
                        $meta->staff = true;
                        $meta->member = true;
                        break;
                    case self::MAKETEER_ROLE:
                        $meta->maketeer = true;
                        $meta->member = true;
                        break;
                }
            }
        }

        $active_plans = get_posts([
            'post_type' => self::PAUPRESS_PLAN,
            'author' => $user_id,
            'meta_key' => self::PP_ITEM_STATUS,
            'meta_value' => self::ACTIVE
        ]);
        if (count($active_plans) > 0)
            $meta->member = true;

        return $meta;
    }

}
