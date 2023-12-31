<?php

namespace Telegram\Bot\Commands;

class RegistrationStepInvitationCodeCommand extends Command
{
	/**
	 * @var string Command Name
	 */
	protected $name = "registration_step_invitation_code";
	
	/**
	 * @var string Command Description
	 */
	protected $description = "Step of registration with invitation code";
	
	/**
	 * @inheritdoc
	 */
	public function handle()
	{
		$update = $this->getUpdate();

		$telegram_id = $update->getMessage()->chat->id;
		
		$result = user_is_verified($telegram_id);

		$options = [
			'chat_id' => $telegram_id,
		];

        if(!empty($result['user']['invitation_code_id'])) {
            $options['text'] = __('You are already registered', $result['user']['language']);
            $this->telegram->sendMessage($options);
            return false;
        }
		
		$options['text'] = __('Please provide your invitation code', $result['user']['language']);
		$this->telegram->sendMessage($options);

        set_command_to_last_message($this->name, $telegram_id);
	}
}