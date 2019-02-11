<?php
namespace mod_msocial;

class social_user_cache
{
    protected $usertosocialmapping = null;
    protected $socialtousermapping = null;
    protected $socialusernametosocialid = null;
    
    public function __construct($mapusers) {
        $this->load_cache($mapusers);
    }
    /** Maps social ids to moodle's user ids
     *
     * @param int $socialid */
    public function get_userid($socialid) {
        return isset($this->socialtousermapping[$socialid]) ? $this->socialtousermapping[$socialid] : null;
    }
    /** Maps a Moodle's $user to a socialuserid in the social media.
     *
     * @param \stdClass|int $user user record or userid
     * @return social_user  */
    public function get_social_userid($user) {
        if ($user instanceof \stdClass) {
            $userid = $user->id;
        } else {
            $userid = (int) $user;
        }
        return isset($this->usertosocialmapping[$userid]) ? $this->usertosocialmapping[$userid] : null;
    }
    /**
     * Maps a social network username to its internal userid if known.
     * @param string $socialname
     */
    public function get_socialuserid_from_socialname(string $socialname) {
        return isset($this->socialusernametosocialid[$socialname]) ? $this->socialusernametosocialid[$socialname] : null;
    }
    protected function load_cache($mapusers) {
        if ($this->usertosocialmapping == null || $this->socialtousermapping == null) {
            $this->usertosocialmapping = [];
            $this->socialtousermapping = [];
            $this->socialusernametosocialid = [];
            foreach ($mapusers as $record) {
                $this->socialusernametosocialid[$record->socialname] = $record->socialid;
                $this->socialtousermapping[$record->socialid] = $record->userid;
                $this->usertosocialmapping[$record->userid] = new social_user($record->socialid, $record->socialname, isset($record->link) ? $record->link : '');
            }
        }
    }
}

