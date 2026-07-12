<?php

test('the app sends a signed-out visitor to the welcome screen', function () {
    $this->get('/')->assertRedirect(route('auth.welcome'));
});
