<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Notifications\ServiceProviderMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Send a message to a service provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function send(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceProvider = ServiceProvider::findOrFail($id);

        // Send notification via email
        try {
            Notification::route('mail', $serviceProvider->email)
                ->notify(new ServiceProviderMessage(
                    $serviceProvider,
                    Auth::user(),
                    $request->subject,
                    $request->content
                ));

            // Update the last activity
            $serviceProvider->updateLastActivity();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
