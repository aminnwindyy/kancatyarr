<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct(Builder $query = null)
    {
        $this->query = $query ?: User::query();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @var User $user
     */
    public function map($user): array
    {
        return [
            $user->user_id,
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->phone,
            $user->is_active ? 'فعال' : 'غیرفعال',
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام',
            'نام خانوادگی',
            'ایمیل',
            'تلفن',
            'وضعیت',
            'تاریخ ثبت‌نام',
        ];
    }
}
