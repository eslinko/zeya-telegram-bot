<?php

namespace Telegram\Bot\Commands;

use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class MyInterestsAndValuesCommand.
 */
class MyInterestsAndValuesCommand extends Command
{
	/**
	 * @var string Command Name
	 */
	protected $name = "my_interests_and_values";
	
	/**
	 * @var string Command Description
	 */
	protected $description = "Description_My interests and values";
	
	/**
	 * {@inheritdoc}
	 */
	public function handle()
	{
		$update = $this->getUpdate();
		
		$telegram_id = $update->getMessage()->chat->id;
		
		$user = user_is_verified($telegram_id);
		
		if(!$user['status']) {
			return false;
		}

        if(empty($user['user']['interests_description']) && empty($user['user']['calculated_interests'])) {
            $this->telegram->triggerCommand('set_user_interests', $update);
            return false;
        }

        $options = [
            'chat_id' => $telegram_id,
        ];

        // если выполнили много раз подряд команду то сработает ошибка, нельзя запускать команду чаще чем каждые 10 секунд
//        if((time() - ((int)$user['user']['last_request_to_chatgpt_date'])) < 10) {
//            $options['text'] = __("Do not execute this command more than once every 10 seconds. Try later", $user['user']['language']);
//            $this->telegram->sendMessage($options);
//            return false;
//        }

        $lcApi = new \LCAPPAPI();
        $data = $lcApi->makeRequest('get-user-interests-list', ['telegram_id' => $telegram_id, 'user_lang' => !empty($user['user']['language']) ? $user['user']['language'] : 'en']);

        if(empty($data['list_of_interests'])) {
            $options['text'] = __("Your list is empty. Should I generate a new one?", $user['user']['language']);
            $options['reply_markup'] = Keyboard::make([
                'inline_keyboard' =>  [
                    [
                        Keyboard::inlineButton([
                            'text' => __('Yes', $user['user']['language']),
                            'callback_data' => 'set_user_interests'
                        ]),
                        Keyboard::inlineButton([
                            'text' => __('No', $user['user']['language']),
                            'callback_data' => 'help'
                        ])
                    ]
                ],
                'resize_keyboard' => true,
            ]);
            $this->telegram->sendMessage($options);
            exit;
        }
		
		$options['text'] = $data['list_of_interests'];

        $options['reply_markup'] = Keyboard::make([
            'inline_keyboard' =>  [
                [
                    Keyboard::inlineButton([
                        'text' => __('Btn_Add new interests', $user['user']['language']),
                        'callback_data' => 'add_new_interests'
                    ]),
                    Keyboard::inlineButton([
                        'text' => __('Clear all interests', $user['user']['language']),
                        'callback_data' => 'clear_all_interests_question'
                    ])
                ]
            ],
            'resize_keyboard' => true,
        ]);
		$this->telegram->sendMessage($options);

		//set_command_to_last_message($this->name, $telegram_id);
	}
}
