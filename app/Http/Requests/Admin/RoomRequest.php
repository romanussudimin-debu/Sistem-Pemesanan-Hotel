<?php

// app/Http/Requests/Admin/RoomRequest.php

namespace App\Http\Requests\Admin;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoomRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'number' => ['required', 'string', 'max:10'],
            'floor' => ['nullable', 'integer', 'min:-5', 'max:100'],
            'status' => ['required', Rule::enum(RoomStatus::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            // Asumsi Tahap 3 no. 6: nomor kamar unik per CABANG (bukan hanya per tipe)
            $type = RoomType::find($this->input('room_type_id'));
            if (! $type) {
                return;
            }

            $exists = Room::where('number', $this->input('number'))
                ->whereHas('roomType', fn ($q) => $q->where('branch_id', $type->branch_id))
                ->when($this->route('room'), fn ($q, $room) => $q->whereKeyNot($room->id))
                ->exists();

            if ($exists) {
                $v->errors()->add('number', __('validation.custom.number.unique_branch', ['number' => $this->input('number')]));
            }
        }];
    }
}
