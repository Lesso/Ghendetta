<?php
/**
 * @copyright (C) 2012 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jens Segers <jens at iRail.be>
 * @author Hannes Van De Vreken <hannes at iRail.be>
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class user_model extends CI_Model {
    
    function get($fsqid) {
        return $this->db->where('fsqid', $fsqid)->get('users')->row_array();
    }
    
    function insert($user) {
        $user['registered'] = time();
        
        $this->db->insert('users', $user);
        return $this->db->insert_id();
    }
    
    function update($fsqid, $user) {
        return $this->db->where('fsqid', $fsqid)->update('users', $user);
    }
    
    function exists($fsqid) {
        return $this->db->where('fsqid', $fsqid)->count_all_results('users') != 0;
    }
    
    function get_all($limit = FALSE) {
        if ($limit) {
            return $this->db->get('users')->limit($limit)->result_array();
        } else {
            return $this->db->get('users')->result_array();
        }
    }
    
    function get_all_rand($limit = FALSE) {
        if ($limit) {
            return $this->db->order_by('RAND()')->limit($limit)->get('users')->result_array();
        } else {
            return $this->db->order_by('RAND()')->get('users')->result_array();
        }
    }
    
    function count() {
        return $this->db->count_all('users');
    }
    
    /**
     * Get a specific user, with total points
     * @param int $userid
     */
    function get_stats($fsqid) {
        $query = '
        	SELECT fsqid, firstname, lastname, picurl, clanid, COALESCE(FLOOR(SUM(checkins.points)), 0) as points, COUNT(checkins.checkinid) as battles
        	FROM checkins
        	JOIN users ON users.fsqid = checkins.userid
        	WHERE date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
        	AND userid = ?';
        
        return $this->db->query($query, array($fsqid))->row_array();
    }
    
    /**
     * Get a specific user, with total points and ranking in clan
     * @param int $fsqid
     */
    function get_ranked($fsqid) {
        $user = $this->get($fsqid);
        
        if (!$user) {
            return FALSE;
        }
        
        $query = '
        	SELECT * 
    		FROM (
    		  	SELECT fsqid, firstname, lastname, picurl, clanid, points, battles, @rownum:=@rownum+1 as rank 
              	FROM (
              		SELECT fsqid, firstname, lastname, picurl, clanid, COALESCE(FLOOR(SUM(checkins.points)), 0) as points, COUNT(checkins.checkinid) as battles
            		FROM users 
            		LEFT JOIN checkins ON users.fsqid = checkins.userid AND checkins.date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
            		WHERE users.clanid = ?
            		GROUP BY users.fsqid
            		ORDER BY points desc, CASE fsqid WHEN ? THEN 1 ELSE 0 END
            		) t, (SELECT @rownum:=0) r
            	) t
            WHERE fsqid = ?';
        
        return $this->db->query($query, array($user['clanid'], $fsqid, $fsqid))->row_array();
    }

}