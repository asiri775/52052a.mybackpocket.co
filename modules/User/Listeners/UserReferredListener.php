<?php
namespace Modules\User\Listeners;

use Modules\Referalprogram\Models\ReferralLink;
use Modules\Referalprogram\Models\ReferralRelationship;
use Modules\User\Events\UserReferredEvent;

class UserReferredListener
{
    public function handle(UserReferredEvent $event)
    {
        if ($event->referralId != null) {
            $referral = ReferralLink::find($event->referralId);
            if (!is_null($referral)) {
                ReferralRelationship::create(['referral_link_id' => $referral->id, 'user_id' => $event->user->id]);
                if ($referral->program->uri === 'register') {
                    // User who was sharing link
                    $provider = $referral->user;
                    $provider->promo_credits = $provider->promo_credits + $referral->program->amount;
                    $provider->save();
                    // User who used the link
                    $user = $event->user;
                    $user->promo_credits = $user->promo_credits + $referral->program->amount;
                    $user->save();
                }
            }
        }
    }
}