<?php

namespace Telegram\Bot\Commands;

use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class MyMatchesCommand.
 */
class MyMatchesCommand extends Command
{
	/**
	 * @var string Command Name
	 */
	protected $name = "my_matches";
	
	/**
	 * @var string Command Description
	 */
	protected $description = "My matches";
	
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
        $options = [
            'chat_id' => $telegram_id,
        ];


        $url = parse_url(getenv('API_URL'));
        $lcApi = new \LCAPPAPI();
        $data = $lcApi->makeRequest('get-user-matches', ['telegram_id' => $telegram_id]);

        if($data['status'] === 'error' || empty($data)) {
            $options['text'] = __("Error! Try again later.", $user['user']['language']);
            $this->telegram->sendMessage($options);
            return false;
        } else {
            if(empty($data['matches'])) {
                $options['text'] = __('You do not have matches', $user['user']['language']);
            } else {
                $options['text'] = __('Your matches', $user['user']['language']);
                $i=1;
                $inline_keyboard = [];
                foreach ($data['matches'] as $item) {
                    if($item['connected'])
                        $hide_connect_button = 'yes';
                    else
                        $hide_connect_button = 'no';
                    $user_name_text = $item['user']['publicAlias'];
                    if(!empty($item['user']['telegram_alias']))$user_name_text = '@'.$item['user']['telegram_alias'].' ('.$user_name_text.')';
                    $text = $i.'. '.$user_name_text.' '.__('created on', $user['user']['language']).' '.date('j/m/y',strtotime($item['timestamp']))."\n";
                    $inline_keyboard[]=[
                        Keyboard::inlineButton([
                            'text' => $text,
                            'web_app' => ['url' => $url['scheme']."://".$url['host'].'/frontend/web/user_profile/user_profile.htm?user_id='.$item['user']['id'].'&hide_connect_button='.$hide_connect_button]
                        ])
                    ];


                    $text = __('Open chat with', $user['user']['language']).' '.$user_name_text;
                    $inline_keyboard[]=[
                        Keyboard::inlineButton([
                            'text' => $text,
                            'url' => 'tg://user?id='.$item['user']['telegram']
                        ])
                    ];
                    $i++;
                }
                $options['reply_markup'] = Keyboard::make([
                    'inline_keyboard' =>  $inline_keyboard,
                    'resize_keyboard' => true
                ]);
            }

        }


		/*$options['reply_markup'] = Keyboard::make([
			'inline_keyboard' =>  [
				[
					Keyboard::inlineButton([
						'text' => __('Explore CE (tinder)', $user['user']['language']),
						'web_app' => ['url' => $url['scheme']."://".$url['host'].'/frontend/web/swipe/swipe.htm']
					])
				]
			],
			'resize_keyboard' => true
		]);*/
		
		$this->telegram->sendMessage($options);
	}
}
