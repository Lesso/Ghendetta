<?php
/**
 * @copyright (C) 2012 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jens Segers <jens at iRail.be>
 * @author Hannes Van De Vreken <hannes at iRail.be>
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class clan_model extends CI_Model {
    
    function insert($clan) {
        $this->db->insert('clans', $clan);
        return $this->db->insert_id();
    }
    
    function update($clanid, $clan) {
        return $this->db->where('clanid', $clanid)->update('clans', $clan);
    }
    
    function get($clanid) {
        return $this->db->where('clanid', $clanid)->get('clans')->row_array();
    }
    
    function get_all() {
        return $this->db->get('clans')->result_array();
    }
    
    function count() {
        return $this->db->count_all('clans');
    }
    
    function count_members($clanid) {
        return $this->db->where('clanid', $clanid)->count_all_results('users');
    }
    
    /**
     * Get specific clan, with total member points
     * @param int $clanid
     */
    function get_stats($clanid) {
        $query = '
        	SELECT *, COALESCE(FLOOR(SUM(points)), 0) as points, SUM(battles) as battles, COUNT(1) as members
        	FROM (
            	SELECT clans.*, SUM(checkins.points) as points, COUNT(checkins.checkinid) as battles
            	FROM clans
            	LEFT JOIN users ON users.clanid = clans.clanid
            	LEFT JOIN checkins ON users.fsqid = checkins.userid AND checkins.date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
            	WHERE clans.clanid = ?
            	GROUP BY clans.clanid, users.fsqid
            	) as sub
            GROUP BY clanid';
        
        return $this->db->query($query, array($clanid))->row_array();
    }
    
    /**
     * Get all clans, with total member points
     */
    function get_all_stats() {
        $query = '
        	SELECT *, COALESCE(FLOOR(SUM(points)), 0) as points, SUM(battles) as battles, COUNT(1) as members
        	FROM (
            	SELECT clans.*, SUM(checkins.points) as points, COUNT(checkins.checkinid) as battles
            	FROM clans
            	LEFT JOIN users ON users.clanid = clans.clanid
            	LEFT JOIN checkins ON users.fsqid = checkins.userid AND checkins.date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
            	GROUP BY clans.clanid, users.fsqid
            	) sub
            GROUP BY clanid';
        
        return $this->db->query($query)->result_array();
    }
    
    /**
     * Get all members of a specific clan
     * If the third $fsqid is used and that person does not have any checkins, this user will be placed last
     * @param int $clanid
     * @param int $limit
     * @param int $fsqid
     */
    function get_members($clanid, $limit = FALSE, $fsqid = FALSE) {
        $query = '
        	SELECT t.*, @rownum:=@rownum+1 as rank
        	FROM (
        		SELECT fsqid, firstname, lastname, picurl, COALESCE(FLOOR(SUM(checkins.points)), 0) as points, COUNT(checkins.checkinid) as battles,
        			CASE clans.capo WHEN fsqid THEN 1 ELSE 0 END as is_capo
                FROM users
                LEFT JOIN checkins ON users.fsqid = checkins.userid AND checkins.date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
                LEFT JOIN clans ON clans.clanid = users.clanid
                WHERE users.clanid = ?
                GROUP BY users.fsqid
                ORDER BY capo DESC, points DESC ' . ($fsqid ? ', CASE fsqid WHEN ? THEN 1 ELSE 0 END ' : '') . ($limit ? 'LIMIT 0,?' : '') . '
                ) t, (SELECT @rownum:=0) r';
        
        return $this->db->query($query, array($clanid, $fsqid, $limit))->result_array();
    }
    
    /**
     * Get the capo of a clan
     * @param int $clanid
     */
    function get_capo($clanid) {
        $query = "
        	SELECT fsqid, firstname, lastname, picurl, clans.clanid, '1' as rank, COALESCE(FLOOR(SUM(checkins.points)), 0) as points, COUNT(checkins.checkinid) as battles
        	FROM clans
        	JOIN users ON users.fsqid = clans.capo
        	LEFT JOIN checkins ON users.fsqid = checkins.userid AND checkins.date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
        	WHERE clans.clanid = ?
        	GROUP BY users.fsqid";
        
        return $this->db->query($query, array($clanid))->row_array();
    }
    
    /**
     * Suggest clan based on total checkins in the last 7 days between all clans
     */
    function suggest_clan() {
        $query = '
            SELECT clans.*, COALESCE(FLOOR(SUM(checkins.points)), 0) as points
            FROM clans
            LEFT JOIN users ON users.clanid = clans.clanid
            LEFT JOIN checkins ON checkins.userid = users.fsqid AND checkins.date >= UNIX_TIMESTAMP(SUBDATE(now(),7))
            GROUP BY clans.clanid
            ORDER BY points ASC
            LIMIT 0, 1';
        
        return $this->db->query($query)->row_array();
    }

}
