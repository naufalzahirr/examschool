<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('teacherProfile')->latest();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->paginate(30)->withQueryString();

        return view('accounts.index', compact('users'));
    }

    public function create()
    {
        return view('accounts.form', [
            'account' => new User(['role' => User::ROLE_TEACHER, 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['must_change_password'] = $request->boolean('must_change_password');
        $data['is_active'] = $request->boolean('is_active', true);

        $account = User::create($data);
        AuditLog::record('account.created', $account, ['role' => $account->role]);

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $account)
    {
        return view('accounts.form', compact('account'));
    }

    public function update(Request $request, User $account)
    {
        $data = $this->validated($request, $account);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $data['must_change_password'] = $request->boolean('must_change_password');
        $data['is_active'] = $request->boolean('is_active');

        $account->update($data);
        AuditLog::record('account.updated', $account, ['role' => $account->role]);

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $account)
    {
        abort_if((int) $account->id === (int) auth()->id(), 422, 'Akun yang sedang login tidak boleh dihapus.');
        AuditLog::record('account.deleted', $account, ['email' => $account->email, 'username' => $account->username]);
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil dihapus.');
    }

    public function generateTeacherAccounts(Request $request)
    {
        $data = $request->validate([
            'default_password' => ['required', 'string', 'min:6', 'max:80'],
            'only_without_account' => ['nullable', 'boolean'],
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, &$created, &$updated, &$skipped) {
            $query = Teacher::query()->where('is_active', true)->orderBy('id');
            if (!empty($data['only_without_account'])) {
                $query->whereNull('user_id');
            }

            $query->chunkById(100, function ($teachers) use ($data, &$created, &$updated, &$skipped) {
                foreach ($teachers as $teacher) {
                    $username = $this->normalizeUsername($teacher->nip ?: ('guru' . $teacher->id));
                    if (!$username) {
                        $skipped++;
                        continue;
                    }

                    $email = $teacher->kontak && filter_var($teacher->kontak, FILTER_VALIDATE_EMAIL)
                        ? $teacher->kontak
                        : $username . '@guru.local';

                    $user = $teacher->user ?: User::where('username', $username)->first();
                    if ($user) {
                        $user->update([
                            'name' => $teacher->name,
                            'email' => $user->email ?: $email,
                            'username' => $username,
                            'role' => User::ROLE_TEACHER,
                            'is_active' => true,
                        ]);
                        $updated++;
                    } else {
                        $user = User::create([
                            'name' => $teacher->name,
                            'email' => $email,
                            'username' => $username,
                            'password' => Hash::make($data['default_password']),
                            'role' => User::ROLE_TEACHER,
                            'is_active' => true,
                            'must_change_password' => true,
                        ]);
                        $created++;
                    }

                    $teacher->forceFill(['user_id' => $user->id])->save();
                }
            });
        });

        AuditLog::record('teacher_accounts.generated', null, compact('created', 'updated', 'skipped'));

        return back()->with('success', "Generate akun guru selesai. Baru: {$created}, diperbarui: {$updated}, dilewati: {$skipped}. Username guru memakai NIP; password awal sesuai input.");
    }

    private function validated(Request $request, ?User $account = null): array
    {
        $accountId = $account?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($accountId)],
            'username' => ['nullable', 'string', 'max:80', Rule::unique('users', 'username')->ignore($accountId)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$account ? 'nullable' : 'required', 'string', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
        ]);
    }

    private function normalizeUsername(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_.-]+/', '', $value) ?: '';
        return substr($value, 0, 80);
    }
}
