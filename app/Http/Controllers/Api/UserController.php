<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * دریافت اطلاعات کاربر فعلی
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * به‌روزرسانی اطلاعات پروفایل کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'phone_number' => 'sometimes|required|regex:/^09[0-9]{9}$/|unique:users,phone_number,'.$user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
            // اگر شماره تلفن تغییر کند، نیاز به تأیید مجدد دارد
            $user->is_phone_verified = false;
        }
        
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'پروفایل با موفقیت به‌روزرسانی شد',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * تغییر رمز عبور کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات نامعتبر',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!password_verify($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'رمز عبور فعلی صحیح نیست',
            ], 400);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'رمز عبور با موفقیت تغییر یافت',
        ]);
    }

    /**
     * به‌روزرسانی و تایید اطلاعات کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|regex:/^09[0-9]{9}$/|unique:users,phone_number,' . $user->user_id . ',user_id',
            'national_id' => 'sometimes|string|size:10|unique:users,national_id,' . $user->user_id . ',user_id',
            'sheba_number' => 'sometimes|string|size:24|unique:users,sheba_number,' . $user->user_id . ',user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ایجاد آرایه برای به‌روزرسانی
        $updateData = [];

        if ($request->has('first_name') && $user->first_name !== $request->first_name) {
            $updateData['first_name'] = $request->first_name;
            $updateData['is_first_name_verified'] = false;
        }

        if ($request->has('last_name') && $user->last_name !== $request->last_name) {
            $updateData['last_name'] = $request->last_name;
            $updateData['is_last_name_verified'] = false;
        }

        if ($request->has('phone_number') && $user->phone_number !== $request->phone_number) {
            $updateData['phone_number'] = $request->phone_number;
            $updateData['is_phone_verified'] = false;
        }

        if ($request->has('national_id') && $user->national_id !== $request->national_id) {
            $updateData['national_id'] = $request->national_id;
            $updateData['is_national_id_verified'] = false;
        }

        if ($request->has('sheba_number') && $user->sheba_number !== $request->sheba_number) {
            $updateData['sheba_number'] = $request->sheba_number;
            $updateData['is_sheba_verified'] = false;
        }

        if (!empty($updateData)) {
            DB::table('users')->where('user_id', $user->user_id)->update($updateData);

            // دریافت اطلاعات به‌روز شده کاربر
            $user = User::find($user->user_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات کاربری با موفقیت به‌روزرسانی شد',
            'data' => $user,
        ]);
    }

    /**
     * تایید اطلاعات کاربر توسط ادمین
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyUserInfo(Request $request, $userId)
    {
        // فقط ادمین باید دسترسی داشته باشد
        if (!Auth::user() || !Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد',
            ], 404);
        }

        // ایجاد آرایه برای به‌روزرسانی
        $updateData = [];

        if ($request->has('verify_first_name') && $request->verify_first_name) {
            $updateData['is_first_name_verified'] = true;
        }

        if ($request->has('verify_last_name') && $request->verify_last_name) {
            $updateData['is_last_name_verified'] = true;
        }

        if ($request->has('verify_phone') && $request->verify_phone) {
            $updateData['is_phone_verified'] = true;
        }

        if ($request->has('verify_national_id') && $request->verify_national_id) {
            $updateData['is_national_id_verified'] = true;
        }

        if ($request->has('verify_sheba') && $request->verify_sheba) {
            $updateData['is_sheba_verified'] = true;
        }

        if (!empty($updateData)) {
            DB::table('users')->where('user_id', $userId)->update($updateData);

            // دریافت اطلاعات به‌روز شده کاربر
            $user = User::find($userId);
        }

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات کاربر با موفقیت تایید شد',
            'data' => $user,
        ]);
    }

    /**
     * دریافت لیست کاربران برای ادمین
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers(Request $request)
    {
        // فقط ادمین باید دسترسی داشته باشد
        if (!Auth::user() || !Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');

        $users = User::when($search, function ($query, $search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            })
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * دریافت اطلاعات یک کاربر خاص
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserById($userId)
    {
        // فقط ادمین باید دسترسی داشته باشد
        if (!Auth::user() || !Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
