<?php
// FCM menolak token peranti (UNREGISTERED / INVALID_ARGUMENT) — apps dibuang atau token lama. SendPushJob membuang peranti itu.
class PushTokenInvalidException extends MessagingException
{
}
