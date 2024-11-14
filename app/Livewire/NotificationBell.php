<?php


namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public $notifications = [];
    public $unreadCount = 0;

    protected $listeners = ['echo:notifications,User Registered' => 'fetchNotifications'];

    public function mount()
    {
        $this->fetchNotifications();
    }

    public function fetchNotifications()
    {
        $this->notifications = Auth::user()->notifications()->where('read_at', null)->get();
        $this->unreadCount = $this->notifications->count();
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->fetchNotifications();
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
