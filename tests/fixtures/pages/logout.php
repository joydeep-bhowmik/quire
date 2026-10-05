<?php
use function Quire\{redirect, url};

return redirect(url('/'))->forgetCookie('user');
