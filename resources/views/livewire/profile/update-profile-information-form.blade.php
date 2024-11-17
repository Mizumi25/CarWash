<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;



new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $profile_picture;
    public string $phone_number = '';
    public string $country_code = 'PH'; 

    // Country codes and abbreviations
    public array $countries = [
        ['name' => 'Philippines', 'code' => '+63', 'abbr' => 'PH'],
        ['name' => 'Korea', 'code' => '+82', 'abbr' => 'KR'],
        ['name' => 'Japan', 'code' => '+81', 'abbr' => 'JP'],
        ['name' => 'United States', 'code' => '+1', 'abbr' => 'US'],
        ['name' => 'Canada', 'code' => '+1', 'abbr' => 'CA'],
        ['name' => 'United Kingdom', 'code' => '+44', 'abbr' => 'GB'],
        ['name' => 'Australia', 'code' => '+61', 'abbr' => 'AU'],
        ['name' => 'Germany', 'code' => '+49', 'abbr' => 'DE'],
        ['name' => 'France', 'code' => '+33', 'abbr' => 'FR'],
        ['name' => 'India', 'code' => '+91', 'abbr' => 'IN'],
        // Add more countries as needed
    ];
    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->profile_picture = Auth::user()->profile_picture;
        $this->phone_number = Auth::user()->phone_number ?? '';
        $this->country_code = Auth::user()->country_code ?? $this->country_code; 
    
    }

    /**
     * Update the profile information and handle profile picture upload.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'profile_picture' => 'image|max:12288',
        ]);
        

        $user->fill($validated);
        $user->country_code = $this->country_code;
        $user->phone_number = $this->phone_number;

         
        if ($this->profile_picture) {
          $filePath = $this->profile_picture->store('profile_pictures', 'public'); 
          $user->profile_picture = $filePath; 
      }


        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    
};
?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
    
        <!-- Profile Picture -->
        <div>
            <label for="profile_picture" class="block text-sm font-medium text-gray-700">
                {{ __('Profile Picture') }}
            </label>

               <!-- Profile Picture Preview and Upload -->
            <div class="h-full w-full grid place-items-center">
                @if(Auth::user()->profile_picture)
                    <!-- Image Preview -->
                    <img id="profileImagePreview" src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="Profile Picture" class="h-[12rem] w-[12rem] cursor-pointer object-cover rounded-full backdrop-filter backdrop-grayscale backdrop-blur-md backdrop-contrast-200" onclick="document.getElementById('fileInput').click();">

                @else
                    <!-- Default Image if no Profile Picture -->
                    <img id="profileImagePreview" src="path/to/your/default/image.jpg" alt="Default Image" class="h-[12rem] w-[12rem] cursor-pointer object-cover rounded-full" onclick="document.getElementById('fileInput').click();">
                @endif
                <livewire:greendot />

                <!-- Hidden File Input -->
                <input type="file" wire:model="profile_picture" name="profile_picture" id="fileInput" class="hidden" accept="image/*" onchange="previewImage(event)">
            </div>
        </div>

        <!-- Other Profile Fields -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>
        
        <div>
          <x-input-label for="phone_number" :value="__('Phone Number')" />
          <div class="flex items-center">
            <select wire:model="country_code" class="mt-1 block w-1/4 border-gray-300 rounded-md">
                @foreach($countries as $country)
                    <option value="{{ $country['code'] }}">{{ $country['abbr'] }}    ({{ $country['name'] }})</option>
                @endforeach
            </select>
            <x-text-input wire:model="phone_number" id="phone_number" name="phone_number" type="text" maxlength="10" class="mt-1 block w-3/4 ml-2" required />
            <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
        </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>

        
    </form>
</section>


<!-- JavaScript for image preview -->
<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('profileImagePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
