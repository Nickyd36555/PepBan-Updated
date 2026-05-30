<?php
redirect(Auth::isClient() ? '/portal#changelog' : '/login');
