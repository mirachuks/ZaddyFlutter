@extends('admin.layouts.app')

@section('page_title', 'KYC Verification')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Rider KYC Verification</h2>
            <p class="mt-1 text-sm text-slate-500">Review and verify rider profiles for activation.</p>
        </div>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-700">Pending/Inactive</span>
    </div>

    <!-- Search Form -->
    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email, or NIN" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.kyc') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>

    <!-- Riders List -->
    <div class="mt-6 space-y-4">
        @forelse($profiles as $profile)
        <div class="rounded-lg border border-slate-200 p-4">
            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- Rider Basic Info -->
                <div class="flex-1">
                    <div class="flex items-start gap-4">
                        <!-- Profile Image -->
                        <div class="flex-shrink-0">
                            @if($profile->image)
                            <img src="{{ asset('storage/' . $profile->image) }}" alt="Rider" class="h-16 w-16 rounded-lg object-cover border border-slate-200">
                            @else
                            <div class="h-16 w-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                <i class="fas fa-user text-2xl"></i>
                            </div>
                            @endif
                        </div>

                        <!-- Rider Details -->
                        <div>
                            <h3 class="text-lg font-semibold">{{ $profile->user->first_name ?? 'N/A' }} {{ $profile->user->last_name ?? '' }}</h3>
                            <p class="text-sm text-slate-500">{{ $profile->user->email ?? 'N/A' }}</p>
                            <p class="text-sm text-slate-500">{{ $profile->user->mobile_number ?? 'N/A' }}</p>
                            <div class="mt-2">
                                <span class="inline-block rounded-full px-2 py-1 text-xs font-medium {{ $profile->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($profile->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-col gap-2 lg:items-end">
                    <button onclick="openKycModal({{ $profile->id }}, '{{ route('admin.kyc.review', $profile) }}')" class="rounded-lg bg-blue-50 px-4 py-2 text-sm text-blue-700 hover:bg-blue-100">View Details</button>
                </div>
            </div>
        </div>

        <!-- KYC Modal for this Rider -->
        <div id="kycModal{{ $profile->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 p-4">
            <div class="flex min-h-full items-center justify-center">
                <div class="w-full max-w-4xl rounded-lg bg-white p-6 shadow-lg">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b pb-4">
                        <h3 class="text-xl font-semibold">KYC Verification: {{ $profile->user->first_name }} {{ $profile->user->last_name }}</h3>
                        <button onclick="closeKycModal({{ $profile->id }})" class="text-slate-500 hover:text-slate-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="mt-6 grid gap-6 grid-cols-1 md:grid-cols-2">
                        <!-- Personal Details Section -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">Personal Details</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">First Name:</span>
                                    <span class="font-medium">{{ $profile->first_name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Last Name:</span>
                                    <span class="font-medium">{{ $profile->last_name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Legal Name:</span>
                                    <span class="font-medium">{{ $profile->legal_name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">NIN:</span>
                                    <span class="font-medium">{{ $profile->nin ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Gender:</span>
                                    <span class="font-medium">{{ ucfirst($profile->gender) ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">State:</span>
                                    <span class="font-medium">{{ $profile->state ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Service Zone:</span>
                                    <span class="font-medium">{{ $profile->service_zone ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Image -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">Profile Image</h4>
                            @if($profile->image)
                            <img src="{{ asset('storage/' . $profile->image) }}" alt="Profile" class="w-full h-64 object-cover rounded-lg border border-slate-200">
                            @else
                            <div class="w-full h-64 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400">
                                <span class="text-sm">No image provided</span>
                            </div>
                            @endif
                        </div>

                        <!-- License Details -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">License Details</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">License Number:</span>
                                    <span class="font-medium">{{ $profile->license_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Expiry Date:</span>
                                    <span class="font-medium">{{ $profile->license_expiry_date ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- License Images -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">License Images</h4>
                            <div class="space-y-2">
                                @if($profile->license_image)
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">Front:</p>
                                    <img src="{{ asset('storage/' . $profile->license_image) }}" alt="License Front" class="w-full h-40 object-cover rounded border border-slate-200">
                                </div>
                                @endif
                                @if($profile->license_back_image)
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">Back:</p>
                                    <img src="{{ asset('storage/' . $profile->license_back_image) }}" alt="License Back" class="w-full h-40 object-cover rounded border border-slate-200">
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Bike Details -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">Bike Details</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Brand:</span>
                                    <span class="font-medium">{{ $profile->bike_brand ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Model:</span>
                                    <span class="font-medium">{{ $profile->bike_model ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Production Year:</span>
                                    <span class="font-medium">{{ $profile->bike_production_year ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Plate Number:</span>
                                    <span class="font-medium">{{ $profile->bike_plate_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Color:</span>
                                    <span class="font-medium">{{ $profile->bike_color ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Engine Number:</span>
                                    <span class="font-medium text-xs">{{ $profile->bike_engine_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Chassis Number:</span>
                                    <span class="font-medium text-xs">{{ $profile->bike_chassis_number ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bike Image -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">Bike Image</h4>
                            @if($profile->bike_image)
                            <img src="{{ asset('storage/' . $profile->bike_image) }}" alt="Bike" class="w-full h-56 object-cover rounded-lg border border-slate-200">
                            @else
                            <div class="w-full h-56 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400">
                                <span class="text-sm">No image provided</span>
                            </div>
                            @endif
                        </div>

                        <!-- Guarantor Details -->
                        <div class="col-span-full border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">Guarantor Details</h4>
                            @if($profile->guarantors && is_array($profile->guarantors) && count($profile->guarantors) > 0)
                            <div class="space-y-4">
                                @foreach($profile->guarantors as $index => $guarantor)
                                <div class="border-t pt-4 first:border-t-0 first:pt-0">
                                    <p class="font-medium text-sm mb-2">Guarantor {{ $index + 1 }}</p>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Name:</span>
                                            <span class="font-medium">{{ $guarantor['name'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Phone:</span>
                                            <span class="font-medium">{{ $guarantor['phone'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Address:</span>
                                            <span class="font-medium">{{ $guarantor['address'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Relationship:</span>
                                            <span class="font-medium">{{ $guarantor['relationship'] ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-sm text-slate-500">No guarantor information provided</p>
                            @endif
                        </div>

                        <!-- Bank Details -->
                        <div class="col-span-full border rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-slate-800">Bank Details</h4>
                            <div class="grid gap-2 text-sm md:grid-cols-2">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Account Name:</span>
                                    <span class="font-medium">{{ $profile->bank_account_name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Account Number:</span>
                                    <span class="font-medium">{{ $profile->bank_account_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Bank Name:</span>
                                    <span class="font-medium">{{ $profile->bank_name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Bank Code:</span>
                                    <span class="font-medium">{{ $profile->bank_code ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer with Action Buttons -->
                    <div class="mt-6 flex gap-2 border-t pt-4">
                        <form method="POST" action="{{ route('admin.kyc.review', $profile) }}" class="flex gap-2 flex-1">
                            @csrf
                            <button type="submit" name="status" value="approved" class="flex-1 rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                                <i class="fas fa-check mr-2"></i>Activate Rider
                            </button>
                            <button type="submit" name="status" value="rejected" class="flex-1 rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                <i class="fas fa-times mr-2"></i>Reject
                            </button>
                        </form>
                        <button onclick="closeKycModal({{ $profile->id }})" class="rounded-lg bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-8 text-center">
            <p class="text-slate-500">No pending or inactive riders found.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $profiles->links() }}
    </div>
</div>

<!-- Modal Scripts -->
<script>
    function openKycModal(profileId, reviewUrl) {
        const modal = document.getElementById('kycModal' + profileId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeKycModal(profileId) {
        const modal = document.getElementById('kycModal' + profileId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.id && event.target.id.startsWith('kycModal')) {
            event.target.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });
</script>

<style>
    .grid {
        display: grid;
    }

    .gap-6 {
        gap: 1.5rem;
    }

    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    @media (min-width: 768px) {
        .md\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .md\:flex-row {
            flex-direction: row;
        }

        .md\:items-center {
            align-items: center;
        }

        .md\:justify-between {
            justify-content: space-between;
        }
    }

    @media (min-width: 1024px) {
        .lg\:flex-row {
            flex-direction: row;
        }

        .lg\:items-center {
            align-items: center;
        }

        .lg\:items-end {
            align-items: flex-end;
        }

        .lg\:justify-between {
            justify-content: space-between;
        }
    }
</style>
@endsection