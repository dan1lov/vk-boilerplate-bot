<?php return [
    'menu' => [
        'aliases' => [],
        'forManager' => false,
        'execute' => function () {
            global $user;

            $username = $user->username ?? getTemplate('default.noname');
            $message = getTemplate('menu.default', $user->user_id, $username);
            $keyboard = getKeyboardMenuDefault();

            return [$message, $keyboard];
        }],

    'snack' => [
        'aliases' => [],
        'forManager' => false,
        'execute' => function () {
            $message = getTemplate('snack.default');
            $action = 'show_snackbar';

            return [$message, $action];
        }],

    'link' => [
        'aliases' => [],
        'forManager' => false,
        'execute' => function () {
            $link = 'https://vk.com/ffwturtle';
            $action = 'open_link';

            return [$link, $action];
        }],

    'step' => [
        'aliases' => [],
        'forManager' => false,
        'execute' => function () {
            $message = getTemplate('step.default');
            $keyboard = getKeyboardStepDefault();

            return [$message, $keyboard];
        }],

    'signup' => [
        'aliases' => ['signup', 'sign up'],
        'forManager' => false,
        'execute' => function () {
            global $message, $payload, $settings, $temp, $user;
            $action = $temp->a ?? $payload->a ?? null;

            switch ($action) {
                case 'username':
                    $username = $message->text ?? getTemplate('default.undefined');
                    if (empty($username)) {
                        $temp->save();
                        return [getTemplate('signup.empty-username')];
                    }

                    unset($temp->a);
                    $temp->username = $username;
                    $temp->save();

                    $message = getTemplate('signup.username', $username);
                    $keyboard = getKeyboardSignupUsername();
                    break;

                case 'confirm':
                    $temp->clear();
                    changeUserField('username', $user->user_id, $temp->username);

                    $message = getTemplate('signup.success');
                    $keyboard = getKeyboardSignupBack();
                    break;
                case 'dismiss':
                    $temp->clear();

                    $message = getTemplate('signup.cancel');
                    $keyboard = getKeyboardSignupBack();
                    break;

                default:
                    $temp = new VKHP\Scenarios($settings->tsf, $user->user_id);
                    $temp->__onetime = true;
                    $temp->c = 'signup';
                    $temp->a = 'username';
                    $temp->save();

                    $message = getTemplate('signup.default');
                    break;
            }
            return [$message, $keyboard ?? null];
        }],
];
