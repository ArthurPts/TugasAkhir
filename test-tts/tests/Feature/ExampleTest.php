<?php

it('redirects home to songs catalog', function () {
    $response = $this->get('/');

    $response->assertRedirect('/songs');
});
