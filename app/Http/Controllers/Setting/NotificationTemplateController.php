<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $this->ensureDefaultTemplates();

        $templates = NotificationTemplate::query()
            ->orderBy('template_name')
            ->orderBy('template_key')
            ->get();

        return view('settings.templates', compact('templates'));
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $data = $request->validate([
            'template_name' => ['required', 'string', 'max:120'],
            'title_template' => ['required', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'template_name' => $data['template_name'],
            'title_template' => $data['title_template'],
            'body_template' => $data['body_template'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Notification template updated successfully.');
    }

    protected function ensureDefaultTemplates(): void
    {
        $defaults = [
            [
                'template_key' => 'appointment_created',
                'template_name' => 'Appointment Created',
                'title_template' => 'New Appointment Request',
                'body_template' => '{{farmer_name}} requested a visit for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_proposed',
                'template_name' => 'Appointment Proposed',
                'title_template' => 'Doctor Shared Slot',
                'body_template' => 'Doctor shared slot for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_doctor_approved',
                'template_name' => 'Doctor Approved Appointment',
                'title_template' => 'Appointment Approved',
                'body_template' => 'Doctor approved your appointment for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_declined',
                'template_name' => 'Appointment Declined',
                'title_template' => 'Appointment Declined',
                'body_template' => 'Doctor declined your appointment request.',
            ],
            [
                'template_key' => 'appointment_otp_sent',
                'template_name' => 'Visit OTP Sent (Legacy)',
                'title_template' => 'Visit OTP Generated',
                'body_template' => 'Share OTP {{otp}} with doctor at visit time.',
            ],
            [
                'template_key' => 'appointment_visit_otp',
                'template_name' => 'Visit OTP Generated',
                'title_template' => 'Visit OTP Generated',
                'body_template' => 'Share OTP {{otp}} with doctor at visit time.',
            ],
            [
                'template_key' => 'appointment_otp_verified',
                'template_name' => 'OTP Verified',
                'title_template' => 'OTP Verified',
                'body_template' => 'OTP verified successfully for {{animal_name}}.',
            ],
            [
                'template_key' => 'treatment_started',
                'template_name' => 'Treatment Started',
                'title_template' => 'Treatment Started',
                'body_template' => 'Doctor started treatment for appointment {{appointment_id}}.',
            ],
            [
                'template_key' => 'appointment_completed',
                'template_name' => 'Treatment Completed',
                'title_template' => 'Treatment Completed',
                'body_template' => 'Doctor completed treatment for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_treatment_updated',
                'template_name' => 'Treatment Updated',
                'title_template' => 'Treatment details updated',
                'body_template' => 'Treatment notes updated for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_followup_suggested',
                'template_name' => 'Follow-up Suggested',
                'title_template' => 'Follow-up visit suggested',
                'body_template' => 'Doctor suggested follow-up for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_rescheduled',
                'template_name' => 'Appointment Rescheduled',
                'title_template' => 'Appointment Rescheduled',
                'body_template' => 'Doctor proposed a new slot for {{animal_name}}.',
            ],
            [
                'template_key' => 'appointment_farmer_approved',
                'template_name' => 'Farmer Approved Appointment',
                'title_template' => 'Appointment Confirmed',
                'body_template' => 'Farmer confirmed appointment {{appointment_id}}.',
            ],
            [
                'template_key' => 'appointment_farmer_cancelled',
                'template_name' => 'Farmer Cancelled Appointment',
                'title_template' => 'Appointment Cancelled',
                'body_template' => 'Farmer cancelled appointment {{appointment_id}}.',
            ],
            [
                'template_key' => 'shop_order_created',
                'template_name' => 'Shop Order Created',
                'title_template' => 'New Shop Order',
                'body_template' => 'Order {{order_code}} has been placed successfully.',
            ],
            [
                'template_key' => 'shop_order_in_progress',
                'template_name' => 'Shop Order In Progress',
                'title_template' => 'Order In Progress',
                'body_template' => 'Order {{order_code}} is now in progress.',
            ],
            [
                'template_key' => 'shop_order_completed',
                'template_name' => 'Shop Order Completed',
                'title_template' => 'Order Completed',
                'body_template' => 'Order {{order_code}} has been completed.',
            ],
            [
                'template_key' => 'shop_payment_paid',
                'template_name' => 'Shop Payment Paid',
                'title_template' => 'Payment Updated',
                'body_template' => 'Payment status for order {{order_code}} is now paid.',
            ],
        ];

        foreach ($defaults as $item) {
            NotificationTemplate::query()->updateOrCreate(
                ['template_key' => $item['template_key']],
                [
                    'template_name' => $item['template_name'],
                    'title_template' => $item['title_template'],
                    'body_template' => $item['body_template'],
                    'is_active' => true,
                ]
            );
        }
    }
}
