<?php

namespace SeattleMakers\Presspoint;
use SeattleMakers\Discord\User_Metadata;

class User_Metadata_Provider
{
    const ROLE_TAXONOMY_ID = "ppu_roles_1506378218";
    const PAUPRESS_PLAN = 'pp_plan';
    const PP_ITEM_STATUS = "_pp_item_status";
    const ACTIVE = "active";
    const STAFF_ROLE = "Staff";
    const MAKETEER_ROLE = "Maketeer";

    private array $updated_users;

    public function __construct()
    {
        $this->updated_users = array();
        add_filter('update_user_metadata', array($this, 'hook_update_user_metadata'), 10, 3);
        add_filter('update_post_metadata', array($this, 'hook_update_post_metadata'), 10, 3);
    }

    public function get_metadata($user_id): User_Metadata
    {
        $meta = new User_Metadata();

        $roles_array = get_user_meta($user_id, self::ROLE_TAXONOMY_ID);
        if (!empty($roles_array)) {
            foreach ($roles_array[0] as $role) {
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
        }

        $active_plans = get_posts([
            'post_type' => self::PAUPRESS_PLAN,
            'author' => $user_id,
            'meta_key' => self::PP_ITEM_STATUS,
            'meta_value' => self::ACTIVE,
            'post_status' => 'any',
        ]);
        if (count($active_plans) > 0)
            $meta->member = true;

        return $meta;
    }


    public function hook_update_post_metadata($check, $object_id, $meta_key): bool|null
    {
        if ($meta_key === self::PP_ITEM_STATUS) {
            $plan = get_post($object_id);

            if ($plan->post_type === self::PAUPRESS_PLAN) {
                $this->user_changed($object_id);
            }
        }

        return $check;
    }

    public function hook_update_user_metadata($check, $object_id, $meta_key): bool|null
    {
        if ($meta_key === self::ROLE_TAXONOMY_ID) {
            $this->user_changed($object_id);
        }

        return $check;
    }

    public function user_changed($user_id): void
    {
        $this->updated_users[$user_id] = true;
    }

    public function flush(): array {
        $metadata_updates = array();
        foreach ($this->updated_users as $user_id => $updated) {
            if ($updated) {
                $metadata_updates[$user_id] = $this->get_metadata($user_id);
            }
        }

        return $metadata_updates;
    }


}
