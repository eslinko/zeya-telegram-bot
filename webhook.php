<?php
require './vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

use Telegram\Bot\Api;
$telegram = new Api(getenv('BOT_ID'));

require './inc/LCAPPAPI.php';
require './inc/ReplyOnAction.php';
require './inc/helpers_func.php';
require './commands/commands.php';
require './inc/Connections.php';
require './inc/TGKeyboard.php';


$lcApi = new \LCAPPAPI(getenv('API_URL'));

$update = $telegram->getWebhookUpdate();

$last_message = $lcApi->makeRequest('get-user-last-message', ['telegram_id' => $update->getMessage()->chat->id]);

$request = $lcApi->makeRequest('set-user-last-message', ['telegram_id' => $update->getMessage()->chat->id, 'message' => json_encode($update->getMessage())],'array', 'POST');

// user is blocked
if(!empty($last_message['user']) && (int) $last_message['user']['status'] === 0) {
    $telegram->sendMessage(['chat_id' => $update->getMessage()->chat->id, 'text' => __('Sorry, but your account has been blocked.', !empty($last_message['user']['language']) ? $last_message['user']['language'] : 'en')]);
    exit;
}

$cur_text = trim($update->getMessage()->text);
if(substr($cur_text, 0, 1) == "/") {
	if(find_count_of_aplha_in_string($cur_text, '/') > 1) {
		$telegram->sendMessage(['chat_id' => $update->getMessage()->chat->id, 'text' => __('Warning: This command is not supported', !empty($request['user']['language']) ? $request['user']['language'] : 'en')]);
		$telegram->triggerCommand('help', $update);
		exit;
	}
	$telegram->commandsHandler(true);
	exit;
}

if(TGKeyboard::processKeyboard($update,$telegram)) exit;

if ($update->isType('callback_query')) {
	$callbackName = $update->callbackQuery->data;

	if (strpos($callbackName, 'choose_language') !== false) {
		reply_on_action_switcher('choose_language', $update, $telegram, $callbackName);
	} else if (strpos($callbackName, 'remove_interest_from_list_by_number') !== false) {
        reply_on_action_switcher('remove_interest_from_list_by_number', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'delete_connection_by_id')!==false) {
        reply_on_action_switcher('delete_connection_by_id', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'confirm_remove_connection_by_id')!==false) {
        reply_on_action_switcher('confirm_remove_connection_by_id', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'accept_connection')!==false) {
        reply_on_action_switcher('accept_connection', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'decline_connection')!==false) {
        reply_on_action_switcher('decline_connection', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'check_connection')!==false) {
        reply_on_action_switcher('check_connection', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'ask_to_revert_connection')!==false) {
        reply_on_action_switcher('ask_to_revert_connection', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'accept_or_decline_pending_connection_by_id')!==false) {
        reply_on_action_switcher('accept_or_decline_pending_connection_by_id', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'resend_invitation')!==false) {
        reply_on_action_switcher('resend_invitation', $update, $telegram, $callbackName);
    } elseif(in_array($callbackName, ['interests_answers_fillup','interests_answers_fillup_ignore_input'])) {
        reply_on_action_switcher($callbackName, $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'expression_choose_expiration')!==false) {
        reply_on_action_switcher('expression_choose_expiration', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'update_json_profile')!==false) {
        reply_on_action_switcher('update_json_profile', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'expression_update_expiration')!==false) {
        reply_on_action_switcher('expression_update_expiration', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'notification_settings')!==false) {
        reply_on_action_switcher('notification_settings', $update, $telegram, $callbackName);
    } elseif(strpos($callbackName,'love_do_for_user_id')!==false) {
        reply_on_action_switcher('love_do_for_user_id', $update, $telegram, $callbackName);
    } else {
		$telegram->triggerCommand($callbackName, $update);
	}
} else {
    //TGKeyboard::processKeyboard($update,$telegram);
	if($last_message['status'] !== 'error') {
		$last_message_object = json_decode($last_message['message']['last_message']);

		if(!empty($last_message_object->reply_markup)) {
			$callbackName = $last_message_object->reply_markup->inline_keyboard[0][0]->callback_data;
            //here we come when we set last message via set_command_to_last_message
            if (strpos($callbackName, 'write_json_profile') !== false) {
                reply_on_action_switcher('write_json_profile', $update, $telegram, $callbackName);
            } else {
                reply_on_action_switcher($callbackName, $update, $telegram, $last_message_object);
            }

		} else {
            //TGKeyboard::processKeyboard($update,$telegram);
			$telegram->commandsHandler(true);
		}
	} else {
        //TGKeyboard::processKeyboard($update,$telegram);
		$telegram->commandsHandler(true);
	}
}
