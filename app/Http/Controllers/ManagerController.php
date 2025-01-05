<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreManagerRequest;
use App\Http\Requests\UpdateManagerRequest;
use App\Http\Requests\SelfUpdateManagerRequest;

class ManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('company')->where('role', 'manager');

        if (Auth::user()->role !== 'superadmin') {
            $query->where('company_id', Auth::user()->company_id);
        }
        if (!empty($request->name)) {
            $query->where('name', 'LIKE', "%{$request->name}%");
        }

        // Sorting
        $sortBy = $request->input('sortBy', 'id'); // Default sorting berdasarkan ID
        $sortDir = $request->input('sortDir', 'asc'); // Default ascending

        if (in_array($sortBy, ['id', 'name', 'email', 'phone'])) { // Validasi kolom yang diizinkan
            if ($sortDir === 'asc') {
                $query->orderBy($sortBy);
            } else {
                $query->orderByDesc($sortBy);
            }
        }

        $manager = $query->paginate(20)->appends(request()->query());

        // Jika tidak ada data
        if ($manager->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $manager
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreManagerRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $password = $data['password'];
            $data['password'] = Hash::make($password);
            $data['role'] = 'manager';
            $user = User::create($data);
            unset($user['password']);
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $user
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseErrors($th->getMessage());
        }
    }



    /**
     * Display the specified resource.
     */
    public function show()
    {
        $user = User::with('company')->findOrFail(Auth::user()->id);
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::with('company')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateManagerRequest $request, User $manager)
    {
        $data = $request->validated();
        $manager->email = $data['email'];
        $manager->name = $data['name'];
        $manager->phone = $data['phone'];
        $manager->company_id = $data['company_id'];
        $manager->address = $data['address'] ?? null;

        if (!empty($data['password'])) {
            $password = Hash::make($data['password']);
            $manager->password = $password;
        }

        $manager->save();

        return response()->json([
            'success' => true,
            'data' => $manager
        ]);
    }

    public function selfUpdate(SelfUpdateManagerRequest $request)
    {
        $data = $request->validated();
        $manager = User::find(Auth::user()->id);
        $manager->name = $data['name'];
        $manager->phone = $data['phone'];
        $manager->address = $data['address'] ?? null;

        if (!empty($data['password'])) {
            $password = $data['password'];
            $manager->password = $password;
        }

        $manager->save();

        return response()->json([
            'success' => true,
            'data' => $manager
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $manager)
    {
        $manager->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus data'
        ]);
    }
}
