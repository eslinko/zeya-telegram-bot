<?php

namespace Telegram\Bot\Commands;

use Telegram\Bot\Keyboard\Keyboard;
use TGKeyboard;

class StartCommand extends Command
{
	/**
	 * @var string Command Name
	 */
	protected $name = "start";
	
	/**
	 * @var string Command Description
	 */
	protected $description = "Start Command to get you started";
	
	/**
	 * @inheritdoc
	 */
	public function handle()
	{
		$update = $this->getUpdate();

		$telegram_id = $update->getMessage()->chat->id;
		
		$lcApi = new \LCAPPAPI();
		$return_data = $lcApi->makeRequest('get-user-by-telegram-id', ['telegram_id' => $telegram_id, 'telegram_language_code' => $update->getMessage()->from->language_code]);
		
//		ob_start();
//		echo "<pre>";
//		var_dump(get_active_languages($telegram_id));
//		echo "</pre>";
//		$debug = ob_get_contents();
//		ob_get_clean();
//		$this->telegram->sendMessage(['chat_id' => $telegram_id, 'text' => $debug]);

		$options = [
			'chat_id' => $telegram_id,
		];
		
		switch ($return_data['status']){
			case 'error':
				$options['text'] = __('Sorry, there was an error, please contact the administrator.', 'en');
				break;
//			case 'user_not_found':
//				$options['text'] = 'Hello! To interact with the bot you must first complete a simple registration!' . $update['callback_query']['data'];
//				$options['reply_markup'] = Keyboard::make([
//					'inline_keyboard' =>  [
//						[
//							Keyboard::inlineButton([
//								'text' => 'Start registration',
//								'callback_data' => 'registration_step_2'
//							])
//						]
//					],
//					'resize_keyboard' => true,
//				]);
//				break;
			default:
				$options = $this->chooseStep($options, $return_data['user'], $telegram_id);
				break;
		}
        if(empty($return_data['user']['language']))
            $this->telegram->sendMessage(['chat_id' => $telegram_id, 'text' => __('To get started, you have two options', 'en')]);
        else
            $this->telegram->sendMessage(['chat_id' => $telegram_id, 'text' => __('To get started, you have two options', $return_data['user']['language'])]);

        $this->telegram->sendMessage($options);
	}
	
	private function chooseStep($options, $user, $telegram_id = '') {
		if(empty($user['language'])) {
			$options['text'] = __('Hello. Select a communication language:', 'en');
			
			$languages = get_active_languages($telegram_id);
		
			$options['reply_markup'] = Keyboard::make([
				'inline_keyboard' =>  $languages['keyboards'],
				'resize_keyboard' => true,
			]);
            //TO TURN ON PASSWORD uncomment empty($user['password_hash'])
		} else if(empty($user['full_name']) || empty($user['publicAlias']) /*|| empty($user['password_hash'])*/ || empty($user['invitation_code_id'])) {
			
			if(empty($user['full_name']) || empty($user['publicAlias'])) {
				$options['text'] = __('Hello! To interact with the bot you must first complete a simple registration!', $user['language']);
				$step = 'registration_step_2';
			} else if (empty($user['invitation_code_id'])) {
				$options['text'] = __('Hello. You need to finish registering with the bot.', $user['language']);
				$step = 'registration_step_invitation_code';
			}
            //TO TURN ON PASSWORD uncomment below
            /*else if (empty($user['password_hash'])) {
				$options['text'] = __('Hello. You need to finish registering with the bot.', $user['language']);
				$step = 'registration_step_3';
			}*/
			
			$options['reply_markup'] = Keyboard::make([
				'inline_keyboard' =>  [
					[
						Keyboard::inlineButton([
							'text' => __('Start registration', $user['language']),
							'callback_data' => $step
						])
					]
				],
				'resize_keyboard' => true,
			]);
		} else {
			$options['text'] = sprintf(__("Welcome %s!\nYou’re successfully connected to your Zeya account.\nHow can I help you?", $user['language']), $user['publicAlias']);// . $debug;
			$options['reply_markup'] = Keyboard::make([
				'inline_keyboard' =>  [
					[
						Keyboard::inlineButton([
							'text' => __('View a list of commands.', $user['language']),
							'callback_data' => 'help'
						])
					]
				],
				'resize_keyboard' => true,
			]);
           TGKeyboard::showMainKeyboard($telegram_id,$this->telegram,$user,"\xF0\x9F\x91\x8B");
		}
	
		return $options;
	}
}