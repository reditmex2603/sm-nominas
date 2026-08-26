<?php

test('la raíz redirige al login', function () {
    $this->get(route('home'))->assertRedirect('/login');
});