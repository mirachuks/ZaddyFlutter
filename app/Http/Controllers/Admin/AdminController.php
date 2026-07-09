<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Dispute;
use App\Models\EscrowWallet;
use App\Models\EscrowTransaction;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\RiderProfile;
use App\Models\StaticVirtualAccount;
use App\Models\User;
use App\Http\Controllers\Wallet\UserWalletController;
use App\Models\UserWallet;
use App\Models\Withdrawal;
use App\Mail\ManualPaymentApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function loginForm()
    {
        if ($this->isAdminAuthenticated()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user || ! $user->hasRole('admin')) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Only administrators can access the admin portal.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function registerForm()
    {
        if ($this->isAdminAuthenticated()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:users,email'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number'] ?? null,
            'password' => $data['password'],
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        Auth::login($user);

        return redirect()->route('admin.dashboard')->with('success', 'Admin account created successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $this->ensureAdmin();

        $stats = [
            'customers' => User::whereIn('user_type', ['customer', 'user'])->count(),
            'riders' => User::where('user_type', 'rider')->count(),
            'active_jobs' => Job::whereIn('status', ['open', 'matched', 'in_progress'])->count(),
            'completed_jobs' => Job::where('status', 'completed')->count(),
            'kyc_pending' => RiderProfile::where('status', 'pending')->count(),
            'pending_disputes' => $this->countByStatus(Dispute::query(), 'pending'),
            'pending_withdrawals' => $this->countByStatus(Withdrawal::query(), 'pending'),
            'pending_manual_payments' => EscrowTransaction::where('manual_payment_notified', true)->where('status', 'pending')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function users(Request $request)
    {
        $this->ensureAdmin();

        $query = User::query();
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('user_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.users', compact('users', 'search'));
    }

    public function activeUsers(Request $request)
    {
        $this->ensureAdmin();

        $query = User::where('status', 'active');
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('user_type', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.active_users', compact('users', 'search'));
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $this->ensureAdmin();

        if ($user->hasRole('admin') && $user->id !== Auth::id()) {
            return back()->withErrors(['status' => 'Administrators cannot be suspended from the admin panel.']);
        }

        $status = $request->input('status', 'suspended');
        $user->status = $status;
        $user->save();

        return back()->with('success', 'User status updated.');
    }

    public function riders(Request $request)
    {
        $this->ensureAdmin();

        $query = User::where('user_type', 'rider')->with('riderProfile');
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $riders = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.riders', compact('riders', 'search'));
    }

    public function virtualAccounts(Request $request)
    {
        $this->ensureAdmin();

        $query = StaticVirtualAccount::with(['user']);
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('bank_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('txt_ref', 'like', "%{$search}%")
                    ->orWhere('order_ref', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $virtualAccounts = $query->latest('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.virtual_accounts', compact('virtualAccounts', 'search'));
    }

    public function riderWallets(Request $request)
    {
        $this->ensureAdmin();

        $query = User::where('user_type', 'rider')
            ->with(['userwallet', 'riderProfile']);

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $riders = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        $riderIds = $riders->pluck('id')->all();
        $deliveredRevenue = JobApplication::select('user_rider_id', DB::raw('COALESCE(SUM(jobs.total_price), 0) as delivered_revenue'))
            ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->whereIn('user_rider_id', $riderIds)
            ->where('jobs.status', 'completed')
            ->groupBy('user_rider_id')
            ->pluck('delivered_revenue', 'user_rider_id');

        return view('admin.rider_wallets', compact('riders', 'search', 'deliveredRevenue'));
    }

    public function updateRiderStatus(Request $request, RiderProfile $rider)
    {
        $this->ensureAdmin();

        $status = $request->input('status', 'approved');
        $rider->status = $status;
        $rider->save();

        return back()->with('success', 'Rider status updated.');
    }

    public function updateRider(Request $request, RiderProfile $rider)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:120'],
            'service_zone' => ['nullable', 'string', 'max:120'],
            'plate_number' => ['nullable', 'string', 'max:40'],
            'mobility_brand' => ['nullable', 'string', 'max:80'],
            'mobility_model' => ['nullable', 'string', 'max:80'],
        ]);

        $rider->fill($data);
        $rider->save();

        return back()->with('success', 'Rider profile updated.');
    }

    public function notifyRider(Request $request, RiderProfile $rider)
    {
        $this->ensureAdmin();

        $message = $request->input('message', 'A new admin notice is waiting for you.');

        if ($rider->user) {
            AppNotification::create([
                'user_id' => $rider->user->id,
                'type' => 'admin_notice',
                'payload' => ['message' => $message, 'source' => 'admin_panel'],
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Rider notification queued for delivery.');
    }

    public function assignRiderForm(Request $request)
    {
        $this->ensureAdmin();

        $selectedRider = null;
        if ($request->filled('rider_user_id')) {
            $selectedRider = User::where('id', $request->input('rider_user_id'))
                ->where('user_type', 'rider')
                ->first();
        } elseif ($request->filled('source_user_id')) {
            $selectedRider = User::where('id', $request->input('source_user_id'))
                ->where('user_type', 'rider')
                ->first();
        }

        $selectedJob = null;
        if ($request->filled('job_id')) {
            $selectedJob = Job::find($request->input('job_id'));
        }

        $riders = User::where('user_type', 'rider')
            ->with('riderProfile')
            ->orderBy('first_name')
            ->get();

        $openJobs = Job::where('status', 'open')
            ->with('user')
            ->latest('created_at')
            ->limit(100)
            ->get();

        return view('admin.merge_accounts', compact('selectedRider', 'selectedJob', 'riders', 'openJobs'));
    }

    public function assignRider(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'rider_user_id' => ['required', 'integer', 'exists:users,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ]);

        $rider = User::where('id', $data['rider_user_id'])
            ->where('user_type', 'rider')
            ->first();

        if (! $rider) {
            return back()->withErrors(['rider_user_id' => 'A valid rider account is required.'])->withInput();
        }

        if (! $rider->riderProfile) {
            return back()->withErrors(['rider_user_id' => 'The selected rider account does not have a rider profile.'])->withInput();
        }

        $job = Job::find($data['job_id']);
        if (! $job) {
            return back()->withErrors(['job_id' => 'A valid job is required.'])->withInput();
        }

        if ($job->status !== 'open') {
            return back()->withErrors(['job_id' => 'This job is not open for rider assignment.'])->withInput();
        }

        if ($job->acceptedApplication) {
            return back()->withErrors(['job_id' => 'This job already has an accepted rider assigned.'])->withInput();
        }

        if ($job->user_id === $rider->id) {
            return back()->withErrors(['rider_user_id' => 'You cannot assign the job poster as the rider.'])->withInput();
        }

        try {
            $this->assignJobToRider($job, $rider);
        } catch (\Exception $e) {
            return back()->withErrors(['assignment' => 'Assignment failed: ' . $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Rider assigned to job successfully.');
    }

    public function assignRiderApi(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'rider_user_id' => ['required', 'integer', 'exists:users,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ]);

        $rider = User::where('id', $data['rider_user_id'])
            ->where('user_type', 'rider')
            ->first();

        if (! $rider) {
            return response()->json(['success' => false, 'message' => 'A valid rider account is required.'], 422);
        }

        if (! $rider->riderProfile) {
            return response()->json(['success' => false, 'message' => 'The selected rider account does not have a rider profile.'], 422);
        }

        $job = Job::find($data['job_id']);
        if (! $job) {
            return response()->json(['success' => false, 'message' => 'A valid job is required.'], 422);
        }

        if ($job->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'This job is not open for rider assignment.'], 422);
        }

        if ($job->acceptedApplication) {
            return response()->json(['success' => false, 'message' => 'This job already has an accepted rider assigned.'], 422);
        }

        if ($job->user_id === $rider->id) {
            return response()->json(['success' => false, 'message' => 'You cannot assign the job poster as the rider.'], 422);
        }

        try {
            $application = $this->assignJobToRider($job, $rider);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Assignment failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rider assigned to job successfully.',
            'data' => $application,
        ]);
    }

    protected function assignJobToRider(Job $job, User $rider)
    {
        return DB::transaction(function () use ($job, $rider) {
            if ($job->acceptedApplication) {
                throw new \Exception('This job already has an accepted rider assignment.');
            }

            JobApplication::where('job_id', $job->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $application = JobApplication::create([
                'job_id' => $job->id,
                'user_rider_id' => $rider->id,
                'msg' => 'Assigned by admin',
                'bid_price' => $job->price ?? 0,
                'status' => 'accepted',
            ]);

            if ($job->status !== 'accepted') {
                $job->status = 'accepted';
                $job->save();
            }

            AppNotification::create([
                'user_id' => $rider->id,
                'type' => 'application_accepted',
                'payload' => [
                    'title' => 'Job Assigned',
                    'body' => "You were assigned to the job \"{$job->title}\" by an administrator.",
                ],
                'is_read' => false,
            ]);

            if ($job->user) {
                AppNotification::create([
                    'user_id' => $job->user->id,
                    'type' => 'rider_assigned',
                    'payload' => [
                        'title' => 'Rider Assigned',
                        'body' => "An admin assigned a rider to your job \"{$job->title}\".",
                    ],
                    'is_read' => false,
                ]);
            }

            return $application;
        });
    }

    /**
     * API: Admin credits a user's wallet (manual top-up)
     * POST /api/admin/wallet/topup
     */
    public function apiTopUpUserWallet(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'purpose' => ['sometimes', 'string', 'max:255'],
        ]);

        $payload = [
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'purpose' => $data['purpose'] ?? 'admin_topup',
        ];

        // Ensure the user has a wallet before attempting to credit it
        try {
            \App\Http\Controllers\Wallet\UserWalletController::ensureWallet($payload['user_id']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not ensure user wallet exists.',
                'error' => $e->getMessage(),
            ], 500);
        }

        try {
            $result = \App\Http\Controllers\Wallet\UserWalletController::credit($payload);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to credit user wallet.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User wallet credited.',
            'data' => $result,
        ]);
    }

    public function approveManualPayment(Request $request, EscrowTransaction $transaction)
    {
        $this->ensureAdmin();

        if ($transaction->status !== EscrowTransaction::STATUS_PENDING) {
            return back()->withErrors(['transaction' => 'This payment is not pending approval.']);
        }

        DB::transaction(function () use ($transaction) {
            $transaction->status = EscrowTransaction::STATUS_HELD;
            $transaction->save();

            $job = $transaction->job_id ? Job::find($transaction->job_id) : null;
            $riderId = null;
            $amountToCredit = $transaction->rider_payout ?? null;

            if ($job) {
                if ($job->acceptedApplication) {
                    $acceptedApplication = $job->acceptedApplication;
                    $riderId = $acceptedApplication->user_rider_id;

                    if ($amountToCredit === null) {
                        $amountToCredit = $transaction->balance - ($transaction->platform_fee ?? 0);
                    }

                    if ($acceptedApplication->status !== 'in_progress') {
                        $acceptedApplication->update(['status' => 'in_progress']);
                    }

                    if (! in_array($job->status, ['in_progress', 'completed', 'delivered', 'cancelled'])) {
                        $job->status = 'in_progress';
                    }
                }

                if ($job->payment_status !== 'paid') {
                    $job->payment_status = 'paid';
                }

                $job->save();
            }

            // If this transaction is related to a job, credit the accepted rider.
            if ($riderId && $amountToCredit > 0) {
                UserWalletController::ensureWallet($riderId);
                UserWalletController::credit([
                    'user_id' => $riderId,
                    'amount' => $amountToCredit,
                    'purpose' => 'job_earnings',
                ]);
            }

            // If there's no associated job, treat this as a customer top-up and credit the payer's wallet.
            if (! $job) {
                $payerId = $transaction->user_id;
                $topupAmount = $transaction->balance ?? 0;

                if ($payerId && $topupAmount > 0) {
                    UserWalletController::ensureWallet($payerId);
                    UserWalletController::credit([
                        'user_id' => $payerId,
                        'amount' => $topupAmount,
                        'purpose' => 'topup',
                    ]);
                }
            }

            if ($transaction->user) {
                AppNotification::create([
                    'user_id' => $transaction->user->id,
                    'type' => 'manual_payment_approved',
                    'payload' => [
                        'title' => 'Payment Approved',
                        'body' => 'Your manual payment has been approved and funds have been credited.',
                    ],
                    'is_read' => false,
                ]);

                Mail::to($transaction->user->email)->send(new ManualPaymentApproved($transaction));
            }
        });

        return back()->with('success', 'Manual payment approved.');
    }

    /**
     * API variant: Approve a manual payment and return JSON for admin clients.
     */
    public function approveManualPaymentApi(Request $request, EscrowTransaction $transaction)
    {
        $this->ensureAdmin();

        if ($transaction->status !== EscrowTransaction::STATUS_PENDING) {
            return response()->json(['success' => false, 'message' => 'This payment is not pending approval.'], 400);
        }

        try {
            DB::transaction(function () use ($transaction) {
                $transaction->status = EscrowTransaction::STATUS_HELD;
                $transaction->save();

                $job = $transaction->job_id ? Job::find($transaction->job_id) : null;
                $riderId = null;
                $amountToCredit = $transaction->rider_payout ?? null;

                if ($job) {
                    if ($job->acceptedApplication) {
                        $acceptedApplication = $job->acceptedApplication;
                        $riderId = $acceptedApplication->user_rider_id;

                        if ($amountToCredit === null) {
                            $amountToCredit = $transaction->balance - ($transaction->platform_fee ?? 0);
                        }

                        if ($acceptedApplication->status !== 'in_progress') {
                            $acceptedApplication->update(['status' => 'in_progress']);
                        }

                        if (! in_array($job->status, ['in_progress', 'completed', 'delivered', 'cancelled'])) {
                            $job->status = 'in_progress';
                        }
                    }

                    if ($job->payment_status !== 'paid') {
                        $job->payment_status = 'paid';
                    }

                    $job->save();
                }

                // If this transaction is related to a job, credit the accepted rider.
                if ($riderId && $amountToCredit > 0) {
                    UserWalletController::ensureWallet($riderId);
                    UserWalletController::credit([
                        'user_id' => $riderId,
                        'amount' => $amountToCredit,
                        'purpose' => 'job_earnings',
                    ]);
                }

                // If there's no associated job, treat this as a customer top-up and credit the payer's wallet.
                if (! $job) {
                    $payerId = $transaction->user_id;
                    $topupAmount = $transaction->balance ?? 0;

                    if ($payerId && $topupAmount > 0) {
                        UserWalletController::ensureWallet($payerId);
                        UserWalletController::credit([
                            'user_id' => $payerId,
                            'amount' => $topupAmount,
                            'purpose' => 'topup',
                        ]);
                    }
                }

                if ($transaction->user) {
                    $this->sendManualPaymentApprovedNotification($transaction);
                }
            });
        } catch (\Exception $e) {
            \Log::error('Manual payment approval failed: ' . $e->getMessage(), ['transaction_id' => $transaction->id]);
            return response()->json(['success' => false, 'message' => 'Failed to approve manual payment. See logs.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Manual payment approved and funds credited.']);
    }

    protected function sendManualPaymentApprovedNotification(EscrowTransaction $transaction)
    {
        AppNotification::create([
            'user_id' => $transaction->user->id,
            'type' => 'manual_payment_approved',
            'payload' => [
                'title' => 'Payment Approved',
                'body' => 'Your manual payment has been approved and funds have been credited.',
            ],
            'is_read' => false,
        ]);

        Mail::to($transaction->user->email)->send(new ManualPaymentApproved($transaction));
    }

    public function orders(Request $request)
    {
        $this->ensureAdmin();

        $query = Job::with(['user', 'items']);
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'latest');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('pickup_address', 'like', "%{$search}%")
                    ->orWhere('dropoff_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $query->when($sort === 'oldest', function ($q) {
            return $q->oldest('created_at');
        }, function ($q) {
            return $q->latest('created_at');
        });

        $orders = $query->paginate(20)->appends($request->only(['search', 'sort']));

        return view('admin.orders', compact('orders', 'search', 'sort'));
    }

    public function reports()
    {
        $this->ensureAdmin();

        $periods = [
            [
                'label' => 'Today',
                'range' => now()->format('M d, Y'),
                'value' => Job::whereDate('created_at', today())->sum('platform_charge'),
            ],
            [
                'label' => 'This week',
                'range' => now()->startOfWeek()->format('M d') . ' - ' . now()->endOfWeek()->format('M d, Y'),
                'value' => Job::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('platform_charge'),
            ],
            [
                'label' => now()->translatedFormat('F'),
                'range' => 'Current month',
                'value' => Job::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('platform_charge'),
            ],
            [
                'label' => now()->format('Y'),
                'range' => 'Current year',
                'value' => Job::whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])->sum('platform_charge'),
            ],
            [
                'label' => 'Overall charges',
                'range' => 'All time',
                'value' => Job::sum('platform_charge'),
            ],
        ];

        $customerWalletTotal = $this->walletSummary(
            UserWallet::query()
                ->join('users', 'users.id', '=', 'user_wallets.user_id')
                ->whereIn('users.user_type', ['customer', 'user']),
            'balance'
        )['total'];

        $riderWalletTotal = $this->walletSummary(
            UserWallet::query()
                ->join('users', 'users.id', '=', 'user_wallets.user_id')
                ->where('users.user_type', 'rider'),
            'balance'
        )['total'];

        $escrowTotal = $this->walletSummary(EscrowWallet::query(), 'balance')['total'];
        $totalVirtualAccounts = StaticVirtualAccount::count();

        return view('admin.reports', compact('periods', 'customerWalletTotal', 'riderWalletTotal', 'escrowTotal', 'totalVirtualAccounts'));
    }

    public function kyc(Request $request)
    {
        $this->ensureAdmin();

        $query = RiderProfile::with('user')->where('status', 'pending');
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nin', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $profiles = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.kyc', compact('profiles', 'search'));
    }

    public function reviewKyc(Request $request, RiderProfile $rider)
    {
        $this->ensureAdmin();

        $rider->status = $request->input('status', 'approved');
        $rider->save();

        return back()->with('success', 'KYC review recorded.');
    }

    public function disputes(Request $request)
    {
        $this->ensureAdmin();

        $query = Dispute::with('user');
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $disputes = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.disputes', compact('disputes', 'search'));
    }

    public function resolveDispute(Request $request, Dispute $dispute)
    {
        $this->ensureAdmin();

        $dispute->status = $request->input('status', 'resolved');
        $dispute->resolution_note = $request->input('resolution_note', 'Resolved from admin panel.');
        $dispute->save();

        return back()->with('success', 'Dispute updated.');
    }

    public function withdrawals(Request $request)
    {
        $this->ensureAdmin();

        $query = Withdrawal::with('user');
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhere('admin_note', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $withdrawals = $query->orderByDesc('created_at')->paginate(20)->appends($request->only('search'));

        return view('admin.withdrawals', compact('withdrawals', 'search'));
    }

    public function approveWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        $this->ensureAdmin();

        $withdrawal->status = 'approved';
        $withdrawal->admin_note = $request->input('admin_note', 'Approved by admin');
        $withdrawal->save();

        return back()->with('success', 'Withdrawal approved.');
    }

    public function settings()
    {
        $this->ensureAdmin();

        return view('admin.settings');
    }

    public function updateProfile(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();
        $user->fill($data);
        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = $data['password'];
        $user->save();

        return back()->with('success', 'Password updated.');
    }

    protected function ensureAdmin(): void
    {
        if (! $this->isAdminAuthenticated()) {
            abort(403);
        }
    }

    protected function isAdminAuthenticated(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('admin') === true;
    }

    protected function countByStatus($query, string $status): int
    {
        if (! method_exists($query, 'where')) {
            return 0;
        }

        if (! method_exists($query->getModel(), 'getTable')) {
            return 0;
        }

        $table = $query->getModel()->getTable();
        $columns = app('db')->getSchemaBuilder()->getColumnListing($table);

        if (! in_array('status', $columns, true)) {
            return 0;
        }

        return (int) $query->where('status', $status)->count();
    }

    protected function walletSummary($query, string $column): array
    {
        if (! method_exists($query, 'getModel')) {
            return ['total' => 0.0, 'count' => 0, 'column_available' => false];
        }

        $model = $query->getModel();
        $table = $model->getTable();
        $builder = app('db')->getSchemaBuilder();
        $columnAvailable = $builder->hasColumn($table, $column);

        return [
            'total' => $columnAvailable ? (float) $query->sum($column) : 0.0,
            'count' => (int) $query->count(),
            'column_available' => $columnAvailable,
        ];
    }
}
