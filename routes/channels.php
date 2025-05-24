<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Ticket;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// کانال خصوصی برای تیکت‌ها - فقط ادمین یا مالک تیکت می‌تواند به کانال دسترسی داشته باشد
Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    $ticket = Ticket::findOrFail($ticketId);
    $isAdmin = $user->hasRole('admin') || $user->hasRole('support');
    
    return $isAdmin || $user->id === $ticket->user_id;
}); 