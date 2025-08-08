<div class="w-full max-w-[80rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="max-w-2xl mx-auto">
    <!-- Card -->
    <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
      <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
          My Profile
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Manage your account information and role details.
        </p>
      </div>

      <!-- Role Information -->
      <div class="mb-8 p-4 bg-gray-50 rounded-lg dark:bg-slate-800">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Account Role</h3>
        <div class="flex items-center gap-3">
          @if($user->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
            <span class="inline-flex items-center gap-x-1.5 py-2 px-4 rounded-lg text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-500">
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="m22 2-5 10-5-5 10-5z"/>
              </svg>
              {{ ucwords(str_replace('-', ' ', $user->getRoleNames()->first())) }}
            </span>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              <p><strong>Admin Access:</strong> You have administrative privileges</p>
              @if($user->hasRole('admin'))
                <p><strong>Permissions:</strong> Full system access</p>
              @elseif($user->hasRole('product-manager'))
                <p><strong>Permissions:</strong> Product, Category & Brand management</p>
              @elseif($user->hasRole('order-manager'))
                <p><strong>Permissions:</strong> Order management & customer support</p>
              @elseif($user->hasRole('analytics-viewer'))
                <p><strong>Permissions:</strong> Read-only access to reports & analytics</p>
              @endif
            </div>
          @else
            <span class="inline-flex items-center gap-x-1.5 py-2 px-4 rounded-lg text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500">
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
              </svg>
              Customer
            </span>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              <p><strong>Account Type:</strong> Regular customer account</p>
              <p><strong>Access:</strong> Shopping, orders, and profile management</p>
            </div>
          @endif
        </div>

        @if($user->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
          <div class="mt-4">
            <a href="/admin" target="_blank" class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-amber-600 hover:text-amber-800 disabled:opacity-50 disabled:pointer-events-none dark:text-amber-500 dark:hover:text-amber-400">
              <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1"/>
                <rect width="7" height="5" x="14" y="3" rx="1"/>
                <rect width="7" height="9" x="14" y="12" rx="1"/>
                <rect width="7" height="5" x="3" y="16" rx="1"/>
              </svg>
              Access Admin Panel
              <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"/>
              </svg>
            </a>
          </div>
        @endif
      </div>

      <!-- Profile Form -->
      <form wire:submit="updateProfile">
        <!-- Grid -->
        <div class="grid sm:grid-cols-12 gap-2 sm:gap-6">
          <div class="sm:col-span-3">
            <label class="inline-block text-sm text-gray-800 mt-2.5 dark:text-gray-200">
              Profile photo
            </label>
          </div>
          <!-- End Col -->

          <div class="sm:col-span-9">
            <div class="flex items-center gap-5">
              <div class="inline-block size-16 bg-gray-100 rounded-full overflow-hidden">
                <svg class="size-full text-gray-300" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="0.62854" y="0.359985" width="15" height="15" rx="7.5" fill="white"/>
                  <path d="M8.12421 7.20374C9.21151 7.20374 10.093 6.32229 10.093 5.23499C10.093 4.14767 9.21151 3.26624 8.12421 3.26624C7.0369 3.26624 6.15546 4.14767 6.15546 5.23499C6.15546 6.32229 7.0369 7.20374 8.12421 7.20374Z" fill="currentColor"/>
                  <path d="M11.818 10.5975C10.2992 12.6412 7.42106 13.0631 5.37731 11.5537C5.01171 11.2818 4.69296 10.9631 4.42107 10.5975C4.28982 10.4006 4.27107 10.1475 4.37419 9.94123L4.51482 9.65059C4.84296 8.95684 5.53671 8.51624 6.30546 8.51624H9.95231C10.7023 8.51624 11.3867 8.94749 11.7242 9.62249L11.8742 9.93184C11.968 10.1475 11.9586 10.4006 11.818 10.5975Z" fill="currentColor"/>
                </svg>
              </div>
              <div class="flex gap-x-2">
                <div>
                  <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                      <polyline points="17,8 12,3 7,8"/>
                      <line x1="12" x2="12" y1="3" y2="15"/>
                    </svg>
                    Upload photo
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- End Col -->

          <div class="sm:col-span-3">
            <label for="name" class="inline-block text-sm text-gray-800 mt-2.5 dark:text-gray-200">
              Full name
            </label>
            <div class="hs-tooltip inline-block">
              <svg class="hs-tooltip-toggle ms-1 inline-block size-3 text-gray-400 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
              </svg>
              <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible w-40 text-center z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-slate-700" role="tooltip">
                Displayed on receipts and invoices.
              </span>
            </div>
          </div>
          <!-- End Col -->

          <div class="sm:col-span-9">
            <div class="sm:flex">
              <input wire:model="name" id="name" type="text" class="py-2 px-3 pe-11 block w-full border-gray-200 shadow-sm -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg text-sm relative focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="Enter your full name">
            </div>
            @error('name')
              <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
          </div>
          <!-- End Col -->

          <div class="sm:col-span-3">
            <label for="email" class="inline-block text-sm text-gray-800 mt-2.5 dark:text-gray-200">
              Email
            </label>
          </div>
          <!-- End Col -->

          <div class="sm:col-span-9">
            <input wire:model="email" id="email" type="email" class="py-2 px-3 pe-11 block w-full border-gray-200 shadow-sm text-sm rounded-lg focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="Enter your email address">
            @error('email')
              <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
          </div>
          <!-- End Col -->
        </div>
        <!-- End Grid -->

        <div class="mt-5 flex justify-end gap-x-2">
          <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
            Cancel
          </button>
          <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
            Save changes
          </button>
        </div>
      </form>
    </div>
    <!-- End Card -->
  </div>
</div>
