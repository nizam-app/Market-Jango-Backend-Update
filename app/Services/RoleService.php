<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    protected $roleModel;

    public function __construct(Role $roleModel)
    {
        $this->roleModel = $roleModel;
    }

    public function list()
    {
        return $this->roleModel->query();
    }

    public function activeList()
    {
        return $this->roleModel->where('status', 'Active')->get();
    }

    public function findById($id)
    {
        return $this->roleModel->findOrFail($id);
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'status' => 'Active',
            ]);

            // Sync permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $role = Role::findOrFail($id);
            $role->update([
                'name' => $data['name'],
            ]);

            // Sync permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $role = $this->findById($id);
            $role->update(['status' => 'Deleted']);
            $role->delete();
            return $role;
        });
    }

    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $role = $this->findById($id);
            return $role->forceDelete();
        });
    }

    public function restore($id)
    {
        return DB::transaction(function () use ($id) {
            $role = $this->roleModel->withTrashed()->findOrFail($id);
            $role->update(['status' => 'Active']);
            $role->restore();
            return $role;
        });
    }

    public function getPermissionsWithHierarchy()
    {
        return Permission::with(['children.children'])
            ->parent()
            ->active()
            ->get()
            ->map(function ($parentPermission) {
                return [
                    'id' => $parentPermission->id,
                    'name' => $parentPermission->name,
                    'children' => $parentPermission->children->map(function ($childPermission) {
                        return [
                            'id' => $childPermission->id,
                            'name' => $childPermission->name,
                            'children' => $childPermission->children->map(function ($grandChildPermission) {
                                return [
                                    'id' => $grandChildPermission->id,
                                    'name' => $grandChildPermission->name,
                                ];
                            }),
                        ];
                    }),
                ];
            });
    }
}
