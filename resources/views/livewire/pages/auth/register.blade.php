<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Filament\Notifications\Notification;


new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
         $user = User::create($validated);

        
        $adminUsers = User::where('role', 'admin')->get(); 

        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('A new user has joined')
                ->body('New user registered: ' . $user->email . '. View Users Now.')
                ->sendToDatabase($admin);
        }
        event(new \App\Events\NewUserNotificationEvent($user->email));


        Auth::login($user);
        
        auth()->user()->update(['is_active' => true]);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <div class="my-[10px]">
                <div id="strength-bar" style="border-radius: 2px; height: 7px; width: 0%; background-color: red; transition: width 0.3s;"></div>
            </div>
            <p id="strength-text">Password strength: <span id="strength-label">weak</span></p>


            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
    
    <script>
      const passwordInput = document.querySelector("#password");
      const strengthBar = document.querySelector("#strength-bar");
      const strengthLabel = document.querySelector("#strength-label");
      const strengthText = document.querySelector("#strength-text");
      
      function checkStrength(password) {
          let strength = "weak";
          let width = "25%";
          let color = "red";
          const hasLowerCase = /[a-z]/.test(password);
          const hasUpperCase = /[A-Z]/.test(password);
          const hasNumber = /\d/.test(password);
          const hasSpecialChar = /[!@#$%^&*(),.?':{}|<>]/.test(password);
          const passedChecks = [
              hasLowerCase,
              hasUpperCase,
              hasNumber,
              hasSpecialChar,
          ].filter(Boolean).length;
          if (password.length >= 8) {
              if (passedChecks === 4 || password.length >= 12) {
                  strength = "very strong";
                  width = "100%";
                  color = "#3e88f7";
              } else if (passedChecks >= 3) {
                  strength = "strong";
                  width = "75%";
                  color = "#4caf50";
              } else if (passedChecks >= 2) {
                  strength = "medium";
                  width = "50%";
                  color = "orange";
              }
          }
          strengthBar.style.width = width;
          strengthBar.style.backgroundColor = color;
          strengthLabel.textContent = strength;
          strengthText.style.color = color;
      }
      
      passwordInput.addEventListener("input", (event) => {
          checkStrength(event.target.value);
      });


    </script>
</div>
