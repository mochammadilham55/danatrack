<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-slate-900">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="max-w-xl space-y-6">
        <div class="bg-white rounded-3xl shadow-sm p-7">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-7">
            @include('profile.partials.update-password-form')
        </div>

        <div class="bg-rose-50 rounded-3xl border-2 border-rose-100 p-7">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
