<div class="bg-gray-50 min-h-screen">
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Enhanced Sidebar Navigation -->
            <aside class="lg:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <!-- User Profile Summary -->
                    <div class="text-center mb-8">
                        <div class="relative inline-block mb-4">
                            <div class="w-24 h-24 bg-gradient-to-br from-custom-teal-400 to-custom-teal-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center border-2 border-white">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                        <p class="text-sm text-gray-500">
                            @if($user->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
                                {{ ucwords(str_replace('-', ' ', $user->getRoleNames()->first())) }}
                            @else
                                Premium Member
                            @endif
                        </p>
                        <div class="flex justify-center mt-3 space-x-4 text-sm">
                            <div class="text-center">
                                <p class="font-bold text-gray-900">{{ $stats['orders'] }}</p>
                                <p class="text-gray-500">Orders</p>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-gray-900">{{ $stats['wishlist'] }}</p>
                                <p class="text-gray-500">Wishlist</p>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-gray-900">{{ $stats['addresses'] }}</p>
                                <p class="text-gray-500">Addresses</p>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Menu -->
                    <nav class="space-y-2">
                        <button
                            wire:click="switchTab('profile')"
                            class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 font-medium transition-all duration-200 hover:bg-custom-teal-700 {{ $activeTab === 'profile' ? 'text-white bg-custom-teal-600' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <i class="fas fa-user w-5"></i>
                            <span>Profile Information</span>
                        </button>
                        <button
                            wire:click="switchTab('addresses')"
                            class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 font-medium transition-all duration-200 hover:bg-custom-teal-700 {{ $activeTab === 'addresses' ? 'text-white bg-custom-teal-600' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <i class="fas fa-map-marker-alt w-5"></i>
                            <span>Addresses</span>
                        </button>
                        <button
                            wire:click="switchTab('orders')"
                            class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 font-medium transition-all duration-200 hover:bg-custom-teal-700 {{ $activeTab === 'orders' ? 'text-white bg-custom-teal-600' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <i class="fas fa-shopping-bag w-5"></i>
                            <span>Order History</span>
                        </button>
                        <button
                            wire:click="switchTab('wishlist')"
                            class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 font-medium transition-all duration-200 hover:bg-custom-teal-700 {{ $activeTab === 'wishlist' ? 'text-white bg-custom-teal-600' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <i class="fas fa-heart w-5"></i>
                            <span>Wishlist</span>
                        </button>
                        <button
                            wire:click="switchTab('settings')"
                            class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 font-medium transition-all duration-200 hover:bg-custom-teal-700 {{ $activeTab === 'settings' ? 'text-white bg-custom-teal-600' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <i class="fas fa-cog w-5"></i>
                            <span>Account Settings</span>
                        </button>
                    </nav>

                    <!-- Admin Panel Link -->
                    @if($user->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <a href="/admin" target="_blank" class="w-full py-3 px-4 border border-custom-teal-500 text-custom-teal-600 rounded-xl hover:bg-custom-teal-50 transition-all duration-200 flex items-center justify-center gap-2 font-medium">
                                <i class="fas fa-external-link-alt"></i>
                                Admin Panel
                            </a>
                        </div>
                    @endif

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full py-3 px-4 border border-red-500 text-red-500 rounded-xl hover:bg-red-50 transition-all duration-200 flex items-center justify-center gap-2 font-medium">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1">
                <!-- Profile Information Tab -->
                @if($activeTab === 'profile')
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900 mb-2">Profile Information</h2>
                                <p class="text-gray-600">Manage your personal information and account details</p>
                            </div>
                            <button
                                wire:click="toggleEditProfile"
                                class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 flex items-center gap-2"
                            >
                                <i class="fas fa-edit"></i>
                                {{ $editMode ? 'Cancel Edit' : 'Edit Profile' }}
                            </button>
                        </div>

                        @if(!$editMode)
                            <!-- Profile Display -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-6">
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <label class="block text-sm font-medium text-gray-500 mb-2">First Name</label>
                                        <p class="text-lg font-semibold text-gray-900">{{ explode(' ', $user->name)[0] ?? '' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <label class="block text-sm font-medium text-gray-500 mb-2">Email Address</label>
                                        <p class="text-lg font-semibold text-gray-900">{{ $user->email }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <label class="block text-sm font-medium text-gray-500 mb-2">Phone Number</label>
                                        <p class="text-lg font-semibold text-gray-900">{{ $user->phone ?: 'Not provided' }}</p>
                                    </div>
                                </div>
                                <div class="space-y-6">
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <label class="block text-sm font-medium text-gray-500 mb-2">Last Name</label>
                                        <p class="text-lg font-semibold text-gray-900">{{ explode(' ', $user->name, 2)[1] ?? '' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <label class="block text-sm font-medium text-gray-500 mb-2">Member Since</label>
                                        <p class="text-lg font-semibold text-gray-900">{{ $user->created_at->format('F Y') }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <label class="block text-sm font-medium text-gray-500 mb-2">Account Status</label>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Active
                                        </span>
                                    </div>
                                </div>
                                @if($user->bio)
                                    <div class="md:col-span-2">
                                        <div class="bg-gray-50 rounded-xl p-6">
                                            <label class="block text-sm font-medium text-gray-500 mb-2">Bio</label>
                                            <p class="text-lg font-semibold text-gray-900">{{ $user->bio }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Edit Profile Form -->
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    <p class="text-blue-800 text-sm">You are editing your profile. Changes will be saved when you click "Save Changes".</p>
                                </div>
                            </div>

                            <form wire:submit="updateProfile">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                        <input
                                            wire:model="name"
                                            type="text"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                        />
                                        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                        <input
                                            wire:model="phone"
                                            type="tel"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                        />
                                        @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                        <input
                                            wire:model="email"
                                            type="email"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                        />
                                        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                                        <textarea
                                            wire:model="bio"
                                            rows="4"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            placeholder="Tell us about yourself..."
                                        ></textarea>
                                        @error('bio') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div class="flex gap-4 mt-8">
                                    <button
                                        type="submit"
                                        class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-200"
                                    >
                                        Save Changes
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="toggleEditProfile"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-8 rounded-xl transition-all duration-200"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                <!-- Addresses Tab -->
                @if($activeTab === 'addresses')
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900 mb-2">My Addresses</h2>
                                <p class="text-gray-600">Manage your shipping and billing addresses</p>
                            </div>
                            <button
                                wire:click="toggleAddAddress"
                                class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 flex items-center gap-2"
                            >
                                <i class="fas fa-plus"></i>
                                Add New Address
                            </button>
                        </div>

                        <!-- Address Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Sample Default Address -->
                            <div class="border-2 border-custom-teal-500 rounded-2xl p-6 relative bg-custom-teal-50">
                                <div class="absolute top-4 right-4">
                                    <span class="bg-custom-teal-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                                        <i class="fas fa-check mr-1"></i>Default
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Home Address</h3>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-home mr-2"></i>
                                        Primary Residence
                                    </div>
                                </div>
                                <div class="space-y-2 text-gray-700">
                                    <p><strong>{{ $user->name }}</strong></p>
                                    <p>123 Main Street, Apt 4B</p>
                                    <p>New York, NY 10001</p>
                                    <p>United States</p>
                                    <p class="pt-2">
                                        <i class="fas fa-phone mr-2"></i>{{ $user->phone ?: '+1 (555) 123-4567' }}
                                    </p>
                                </div>
                                <div class="flex gap-3 mt-6">
                                    <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium text-sm transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button class="text-red-600 hover:text-red-700 font-medium text-sm transition-colors">
                                        <i class="fas fa-trash mr-1"></i>Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Sample Secondary Address -->
                            <div class="border border-gray-200 rounded-2xl p-6 hover:border-gray-300 transition-colors">
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Work Address</h3>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-briefcase mr-2"></i>
                                        Business Location
                                    </div>
                                </div>
                                <div class="space-y-2 text-gray-700">
                                    <p><strong>{{ $user->name }}</strong></p>
                                    <p>456 Business Ave, Suite 200</p>
                                    <p>New York, NY 10005</p>
                                    <p>United States</p>
                                    <p class="pt-2">
                                        <i class="fas fa-phone mr-2"></i>+1 (555) 987-6543
                                    </p>
                                </div>
                                <div class="flex gap-3 mt-6">
                                    <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium text-sm transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button class="text-red-600 hover:text-red-700 font-medium text-sm transition-colors">
                                        <i class="fas fa-trash mr-1"></i>Remove
                                    </button>
                                    <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium text-sm transition-colors">
                                        <i class="fas fa-star mr-1"></i>Set as Default
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Add Address Form -->
                        @if($showAddressForm)
                            <div class="mt-8 pt-8 border-t border-gray-200">
                                <h3 class="text-xl font-semibold text-gray-900 mb-6">Add New Address</h3>
                                <form wire:submit="saveAddress">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Address Label</label>
                                            <input
                                                wire:model="addressLabel"
                                                type="text"
                                                placeholder="e.g., Home, Work, Parents' House"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            />
                                            @error('addressLabel') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                            <input
                                                wire:model="addressFullName"
                                                type="text"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            />
                                            @error('addressFullName') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                            <input
                                                wire:model="addressPhone"
                                                type="tel"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            />
                                            @error('addressPhone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                                            <input
                                                wire:model="streetAddress"
                                                type="text"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            />
                                            @error('streetAddress') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                            <input
                                                wire:model="city"
                                                type="text"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            />
                                            @error('city') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                            <input
                                                wire:model="postalCode"
                                                type="text"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                            />
                                            @error('postalCode') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="flex items-center">
                                                <input
                                                    wire:model="isDefaultAddress"
                                                    type="checkbox"
                                                    class="w-4 h-4 text-custom-teal-600 rounded focus:ring-custom-teal-500"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">Set as default address</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex gap-4 mt-8">
                                        <button
                                            type="submit"
                                            class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-200"
                                        >
                                            Save Address
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="toggleAddAddress"
                                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-8 rounded-xl transition-all duration-200"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Order History Tab -->
                @if($activeTab === 'orders')
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">Order History</h2>
                            <p class="text-gray-600">Track and manage your past orders</p>
                        </div>

                        <!-- Order Filters -->
                        <div class="flex flex-wrap gap-4 mb-6">
                            <button class="px-4 py-2 bg-custom-teal-600 text-white rounded-lg font-medium transition-all duration-200">
                                All Orders
                            </button>
                            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-all duration-200">
                                Processing
                            </button>
                            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-all duration-200">
                                Shipped
                            </button>
                            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-all duration-200">
                                Delivered
                            </button>
                        </div>

                        <!-- Orders List -->
                        <div class="space-y-6">
                            @forelse($orders as $order)
                                <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                                            <div>
                                                <h3 class="font-semibold text-gray-900">Order #{{ $order->id }}</h3>
                                                <p class="text-sm text-gray-600">Placed on {{ $order->created_at->format('F j, Y') }}</p>
                                            </div>
                                            <div class="flex items-center gap-4 mt-2 md:mt-0">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                    @if($order->status === 'delivered') bg-green-100 text-green-800
                                                    @elseif($order->status === 'shipped') bg-blue-100 text-blue-800
                                                    @elseif($order->status === 'processing') bg-yellow-100 text-yellow-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    @if($order->status === 'delivered')
                                                        <i class="fas fa-check-circle mr-1"></i>Delivered
                                                    @elseif($order->status === 'shipped')
                                                        <i class="fas fa-truck mr-1"></i>Shipped
                                                    @elseif($order->status === 'processing')
                                                        <i class="fas fa-clock mr-1"></i>Processing
                                                    @else
                                                        <i class="fas fa-circle mr-1"></i>{{ ucfirst($order->status) }}
                                                    @endif
                                                </span>
                                                <p class="text-sm font-medium text-gray-900">Total: ${{ number_format($order->grand_total, 2) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach($order->items->take(2) as $item)
                                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl">
                                                    @if($item->product && $item->product->images)
                                                        @php
                                                            $images = is_string($item->product->images) ? json_decode($item->product->images, true) : $item->product->images;
                                                            $firstImage = is_array($images) ? ($images[0] ?? null) : null;
                                                        @endphp
                                                        @if($firstImage)
                                                            <img src="{{ Storage::url($firstImage) }}" alt="{{ $item->product->name }}" class="w-20 h-20 object-cover rounded-lg">
                                                        @else
                                                            <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                                <i class="fas fa-image text-gray-400"></i>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                            <i class="fas fa-image text-gray-400"></i>
                                                        </div>
                                                    @endif
                                                    <div class="flex-1">
                                                        <h4 class="font-medium text-gray-900">{{ $item->product->name ?? 'Product not found' }}</h4>
                                                        <p class="text-sm text-gray-600">Qty: {{ $item->quantity }}</p>
                                                        <p class="font-semibold text-custom-teal-700">${{ number_format($item->unit_amount, 2) }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if($order->items->count() > 2)
                                                <div class="md:col-span-2 text-center py-4">
                                                    <p class="text-gray-600">+ {{ $order->items->count() - 2 }} more items</p>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex gap-3 mt-6">
                                            <a href="{{ route('my-order-details', $order) }}" class="text-custom-teal-600 hover:text-custom-teal-700 font-medium text-sm transition-colors">
                                                <i class="fas fa-eye mr-1"></i>View Details
                                            </a>
                                            <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium text-sm transition-colors">
                                                <i class="fas fa-redo mr-1"></i>Reorder
                                            </button>
                                            <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium text-sm transition-colors">
                                                <i class="fas fa-download mr-1"></i>Download Invoice
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-shopping-bag text-gray-400 text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No orders yet</h3>
                                    <p class="text-gray-600 mb-6">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                                    <a href="{{ route('all-products') }}" class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200">
                                        Start Shopping
                                    </a>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- Wishlist Tab -->
                @if($activeTab === 'wishlist')
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">My Wishlist</h2>
                            <p class="text-gray-600">Items you've saved for later</p>
                        </div>

                        <!-- Wishlist Content -->
                        <div class="text-center py-12">
                            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-heart text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Your wishlist is empty</h3>
                            <p class="text-gray-600 mb-6">Save items you love to your wishlist and shop them later.</p>
                            <a href="{{ route('all-products') }}" class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200">
                                Browse Products
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Account Settings Tab -->
                @if($activeTab === 'settings')
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">Account Settings</h2>
                            <p class="text-gray-600">Manage your account preferences and security</p>
                        </div>

                        <div class="space-y-8">
                            <!-- Password Section -->
                            <div class="border-b border-gray-200 pb-8">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">Password & Security</h3>
                                <div class="bg-gray-50 rounded-xl p-6">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-medium text-gray-900">Password</h4>
                                            <p class="text-sm text-gray-600">Last updated {{ $user->updated_at->diffForHumans() }}</p>
                                        </div>
                                        <button
                                            wire:click="togglePasswordForm"
                                            class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200"
                                        >
                                            {{ $showPasswordForm ? 'Cancel' : 'Change Password' }}
                                        </button>
                                    </div>

                                    @if($showPasswordForm)
                                        <div class="mt-6 pt-6 border-t border-gray-200">
                                            <form wire:submit="updatePassword">
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                                        <input
                                                            wire:model="currentPassword"
                                                            type="password"
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                                            placeholder="Enter your current password"
                                                        />
                                                        @error('currentPassword') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                                        <input
                                                            wire:model="newPassword"
                                                            type="password"
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                                            placeholder="Enter your new password"
                                                        />
                                                        @error('newPassword') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                                        <input
                                                            wire:model="newPasswordConfirmation"
                                                            type="password"
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                                                            placeholder="Confirm your new password"
                                                        />
                                                        @error('newPasswordConfirmation') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                                    </div>
                                                </div>

                                                <div class="flex gap-4 mt-6">
                                                    <button
                                                        type="submit"
                                                        class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200"
                                                    >
                                                        Update Password
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click="togglePasswordForm"
                                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition-all duration-200"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Notifications Section -->
                            <div class="border-b border-gray-200 pb-8">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">Notifications</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                        <div>
                                            <h4 class="font-medium text-gray-900">Email Notifications</h4>
                                            <p class="text-sm text-gray-600">Receive order updates and promotions via email</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer" checked>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-custom-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-custom-teal-600"></div>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                        <div>
                                            <h4 class="font-medium text-gray-900">SMS Notifications</h4>
                                            <p class="text-sm text-gray-600">Receive order updates via SMS</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-custom-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-custom-teal-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Privacy Section -->
                            <div class="border-b border-gray-200 pb-8">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">Privacy</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                        <div>
                                            <h4 class="font-medium text-gray-900">Profile Visibility</h4>
                                            <p class="text-sm text-gray-600">Make your profile visible to other users</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-custom-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-custom-teal-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Danger Zone -->
                            <div>
                                <h3 class="text-xl font-semibold text-red-600 mb-4">Danger Zone</h3>
                                <div class="border border-red-200 rounded-xl p-6 bg-red-50">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-medium text-red-900">Delete Account</h4>
                                            <p class="text-sm text-red-700">Permanently delete your account and all associated data</p>
                                        </div>
                                        <button class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                                            Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
</div>
