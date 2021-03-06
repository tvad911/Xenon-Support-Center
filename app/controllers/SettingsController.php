<?php

use KodeInfo\Forms\Rules\AccountAddValidator;
use KodeInfo\UserManagement\UserManagement;

class SettingsController extends BaseController
{

    function __construct()
    {
        $this->beforeFilter('has_permission:settings.all', array('only' => array('all')));

    }

    public function all()
    {

        $raw_settings = Settings::all();

        $settings = new StdClass();

        foreach ($raw_settings as $raw_setting) {
            $settings->{$raw_setting->key} = json_decode($raw_setting->value);
        }

        $smtp = new StdClass();
        $smtp->from_address = Config::get('mail.from.address')==null?'':Config::get('mail.from.address');
        $smtp->from_name = Config::get('mail.from.name')==null?'':Config::get('mail.from.name');
        $smtp->reply_to_address = Config::get('mail.reply-to.address')==null?'':Config::get('mail.reply-to.address');
        $smtp->reply_to_name = Config::get('mail.reply-to.name')==null?'':Config::get('mail.reply-to.name');
        $smtp->host = Config::get('mail.host')==null?'':Config::get('mail.host');
        $smtp->username = Config::get('mail.username')==null?'':Config::get('mail.username');
        $smtp->password = Config::get('mail.password')==null?'':Config::get('mail.password');

        $mailgun = new StdClass();
        $mailgun->from_address = Config::get('mail.from.address')==null?'':Config::get('mail.from.address');
        $mailgun->from_name = Config::get('mail.from.name')==null?'':Config::get('mail.from.name');
        $mailgun->reply_to = Config::get('mail.reply-to.address')==null?'':Config::get('mail.reply-to.address');
        $mailgun->api_key = Config::get('services.mailgun.secret')==null?'':Config::get('services.mailgun.secret');
        $mailgun->domain = Config::get('mail.host')==null?'':Config::get('mail.host');
        $mailgun->use_mailgun = Config::get('mail.use_mailgun');

        $settings->smtp = $smtp;
        $settings->mailgun = $mailgun;

        $this->data['settings'] = $settings;

        return View::make('settings', $this->data);
    }

    public function setMailGun()
    {

        if(Config::get('site-config.is_demo')){
            Session::flash('error_msg','Demo : Feature is disabled');
            return Redirect::to('/dashboard');
        }

        if (Input::has('use_mailgun')) {

            $mail_content = "<?php
        return [
	          'driver' => 'mailgun',
	          'host' => '".Input::get('domain')."',
	          'port' => 587,
	          'from' => ['address' => '".Input::get('from_address')."', 'name' => '".Input::get('from_name')."'],
	          'reply-to' => ['address' => '".Input::get('reply_to')."','name' => '".Input::get('from_address')."'],
	          'encryption' => 'tls',
	          'username' => '',
	          'password' => '',
	          'sendmail' => '/usr/sbin/sendmail -bs',
	          'pretend' => false,
	          'use_mailgun' => true,
	    ];";

            $extra_services = "<?php
            return [
	            'mailgun' => [
		        'domain' => '".Input::get('domain')."',
		        'secret' => '".Input::get('api_key')."',
	        ],
            ];";


            \File::put(app_path() . "/config/services.php", $extra_services);

        } else {
            $mail_content = "<?php
        return [
	          'driver' => 'smtp',
	          'host' => '".Input::get('domain')."',
	          'port' => 587,
	          'from' => ['address' => '".Input::get('from_address')."', 'name' => '".Input::get('from_name')."'],
	          'reply-to' => ['address' => '".Input::get('reply_to')."','name' => '".Input::get('from_address')."'],
	          'encryption' => 'tls',
	          'username' => '',
	          'password' => '',
	          'sendmail' => '/usr/sbin/sendmail -bs',
	          'pretend' => false,
	          'use_mailgun' => false,
	    ];";
        }

        \File::put(app_path() . "/config/mail.php", $mail_content);

        RecentActivities::createActivity("Mailgun settings changed by User ID:".Auth::user()->id." User Name:".Auth::user()->name);

        Session::flash('success_msg', trans('msgs.mailgun_settings_updated'));

        return Redirect::to('/settings/all#tab-mailgun');

    }

    public function setSMTP()
    {

        if(Config::get('site-config.is_demo')){
            Session::flash('error_msg','Demo : Feature is disabled');
            return Redirect::to('/dashboard');
        }

        $mail_content = "<?php
        return [
	          'driver' => 'smtp',
	          'host' => '".Input::get('host')."',
	          'port' => 587,
	          'from' => ['address' => '".Input::get('from_address')."', 'name' => '".Input::get('from_name')."'],
	          'reply-to' => ['address' => '".Input::get('reply_to_address')."','name' => '".Input::get('reply_to_name')."'],
	          'encryption' => 'tls',
	          'username' => '".Input::get('username')."',
	          'password' => '".Input::get('password')."',
	          'sendmail' => '/usr/sbin/sendmail -bs',
	          'pretend' => false,
	    ];";

        \File::put(app_path() . "/config/mail.php", $mail_content);

        RecentActivities::createActivity("SMTP Settings changed by User ID:".Auth::user()->id." User Name:".Auth::user()->name);

        Session::flash('success_msg', trans('msgs.smtp_settings_updated'));

        return Redirect::to('/settings/all#tab-smtp');

    }

    public function setMailchimp()
    {

        if(Config::get('site-config.is_demo')){
            Session::flash('error_msg','Demo : Feature is disabled');
            return Redirect::to('/dashboard');
        }


        $values = [
            'use_mailchimp' => Input::has('use_mailchimp'),
            'api_key' => Input::get('api_key')
        ];

        Settings::where('key', 'mailchimp')->update(['value' => json_encode($values)]);

        RecentActivities::createActivity("Mailchimp settings changed by User ID:".Auth::user()->id." User Name:".Auth::user()->name);

        Session::flash('success_msg', trans('msgs.mailchimp_settings_updated'));

        return Redirect::to('/settings/all#tab-mailchimp');

    }

    public function setChat()
    {

        if(Config::get('site-config.is_demo')){
            Session::flash('error_msg','Demo : Feature is disabled');
            return Redirect::to('/dashboard');
        }

        $values = [
            'chat_file_types' => Input::get('chat_file_types'),
            'max_file_size' => Input::get('max_file_size'),
            'enable_attachment_in_chat' => Input::has('enable_attachment_in_chat')
        ];

        Settings::where('key', 'chat')->update(['value' => json_encode($values)]);

        RecentActivities::createActivity("Chat settings changed by User ID:".Auth::user()->id." User Name:".Auth::user()->name);

        Session::flash('success_msg', trans('msgs.chat_settings_updated'));

        return Redirect::to('/settings/all#tab-chat');

    }

    public function setTickets(){

        $values = [
            'should_send_email_ticket_status_change' => Input::has('should_send_email_ticket_status_change'),
            'should_send_email_ticket_reply' => Input::has('should_send_email_ticket_reply'),
            'convert_chat_ticket_no_operators' => Input::has('convert_chat_ticket_no_operators')
        ];

        Settings::where('key', 'tickets')->update(['value' => json_encode($values)]);

        RecentActivities::createActivity("Tickets settings changed by User ID:".Auth::user()->id." User Name:".Auth::user()->name);

        Session::flash('success_msg', trans('msgs.tickets_settings_updated'));

        return Redirect::to('/settings/all#tab-tickets');

    }
}