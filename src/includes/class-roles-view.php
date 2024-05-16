<?php

namespace SeattleMakers;

class Roles_View
{
    private array $roles;

    public function __construct(array $all_roles, array $eligible_roles)
    {
        $this->roles = [];
        foreach ($all_roles as $role) {
            $this->roles[] = new Role_View($role->id, $role->name, eligible: in_array($role->name, $eligible_roles));
        }
    }

    public function set_claimed_roles(array $role_ids): void
    {
        foreach ($this->roles as $role) {
            $role->claimed = in_array($role->id, $role_ids);
        }
    }

    public function eligible(): array
    {
        return array_filter($this->roles, function ($role) {
            return $role->eligible;
        });
    }

    public function missing(): array
    {
        return array_filter($this->roles, function ($role) {
            return $role->eligible && !$role->claimed;
        });
    }
}

class Role_View
{
    public string $id;
    public string $name;
    public bool $eligible;
    public bool $claimed;

    public function __construct($id, $name, $eligible = false, $claimed = false)
    {
        $this->id = $id;
        $this->name = $name;
        $this->eligible = $eligible;
        $this->claimed = $claimed;
    }
}
