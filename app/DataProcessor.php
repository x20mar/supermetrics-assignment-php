<?php

class DataProcessor
{
    private $buckets = array();

    /**
     * array of data buckets
     *
     * @return array
     */
    public function getBuckets()
    {
        return $this->buckets;
    }

    /**
     * Sets Data Buckets
     *
     * @param array $buckets
     * @return void
     */
    public function setBuckets(array $buckets)
    {
        $this->buckets = $buckets;
    }

    /**
     * Adds data to a given bucket. If the bucket does not exist it will be created
     *
     * @param string $bucketName
     * @param mixed $data
     * @return void
     */
    public function addToBucket(string $bucketName, $data)
    {
        if (!array_key_exists($bucketName, $this->buckets)) {
            $this->buckets[$bucketName] = array();
        }
        array_push($this->buckets[$bucketName], $data);
    }

    /**
     * Traverses through data and sorts it into buckets. Each bucket is a given month
     *
     * @param array $posts
     * @return void
     */
    public function sortDataIntoBuckets($posts)
    {
        foreach ($posts as $post) {
            $createdTime = DateTime::createFromFormat(DateTimeInterface::ATOM, $post->created_time);
            $bucketName = $createdTime->format('Y-m');
            $this->addToBucket($bucketName, $post);
        }
    }

    /**
     * finds the average character length of posts per bucket
     *
     * @param string $bucketName
     * @return float
     */
    private function getAverageCharacterLengthOfPostsPerBucket(string $bucketName)
    {
        if (!array_key_exists($bucketName, $this->buckets)) {
            throw new Error('Bucket does not exist');
        }
        $results = array();
        foreach ($this->buckets[$bucketName] as $post) {
            array_push($results, strlen($post->message));
        }
        if (count($results) === 0) {
            return 0;
        }
        return array_sum($results) / count($results);
    }

    /**
     * Loops through each bucket and finds the average character length of posts per bucket
     *
     * @return array
     */
    public function getAverageCharacterLengthOfPosts()
    {
        $result = array();
        $bucketNames = array_keys($this->getBuckets());
        foreach ($bucketNames as $bucketName) {
            $result[$bucketName] = $this->getAverageCharacterLengthOfPostsPerBucket($bucketName);
        }
        return $result;
    }

    /**
     * finds longest post for a given bucket. PII removed from results
     *
     * @param string $bucketName
     * @return array
     */
    private function getLongestPostsByCharacterLengthPerBucket(string $bucketName)
    {
        if (!array_key_exists($bucketName, $this->buckets)) {
            throw new Error('Bucket does not exist');
        }

        // Sort bucket so the post with the longest message is on top
        usort($this->buckets[$bucketName], array(__CLASS__, 'comparePostsByMessageLengthDesc'));

        $santisedPost = $this->buckets[$bucketName][0];

        // Remove PII from results
        unset($santisedPost->message);
        unset($santisedPost->from_name);

        return $santisedPost;
    }

    /**
     * Finds longest post for each bucket
     *
     * @return array
     */
    public function getLongestPostsByCharacterLengthPerMonth()
    {
        $result = array();
        $bucketNames = array_keys($this->getBuckets());
        foreach ($bucketNames as $bucketName) {
            $result[$bucketName] = $this->getLongestPostsByCharacterLengthPerBucket($bucketName);
        }
        return $result;
    }

    /**
     * Gets total number of posts per week
     *
     * @return array
     */
    public function getTotalPostsSplitByWeekNumber()
    {
        $weekNumberBuckets = array();

        $bucketNames = array_keys($this->getBuckets());
        foreach ($bucketNames as $bucketName) {

            foreach ($this->buckets[$bucketName] as $post) {
                $createdTime = DateTime::createFromFormat(DateTimeInterface::ATOM, $post->created_time);
                $year = $createdTime->format('o');
                $weekNumberBucketName = $createdTime->format('W');
                if (!array_key_exists($year, $weekNumberBuckets)) {
                    $weekNumberBuckets[$year] = array();
                }
                if (!array_key_exists($weekNumberBucketName, $weekNumberBuckets[$year])) {
                    $weekNumberBuckets[$year][$weekNumberBucketName] = array();
                }
                array_push($weekNumberBuckets[$year][$weekNumberBucketName], $post);
            }
        }
        $results = array();
        foreach ($weekNumberBuckets as $year => $week) {
            foreach ($week as $key => $value) {
                $results[$year][intval($key)] = count($value);
            }
        }
        return $results;
    }

    /**
     * Calculates the average number of posts per user per month
     *
     * @return array
     */
    public function averageNumberOfPostsPerUserPerMonth()
    {
        $bucketNames = array_keys($this->getBuckets());
        $numberOfMonths = count($bucketNames);
        $userBuckets = array();
        foreach ($bucketNames as $bucketName) {
            foreach ($this->buckets[$bucketName] as $post) {
                $userId = $post->from_id;
                if (!array_key_exists($userId, $userBuckets)) {
                    $userBuckets[$userId] = array();
                }
                array_push($userBuckets[$userId], $post);
            }
        }

        $results = array();
        foreach ($userBuckets as $userId => $posts) {
            $results[$userId] = count($posts) / $numberOfMonths;
        }
        ksort($results, SORT_NATURAL);
        return $results;
    }

    /**
     * Compare function used in getLongestPostPerBucket to order posts by character length
     *
     * @param object $a
     * @param object $b
     * @return int
     */
    private static function comparePostsByMessageLengthDesc($a, $b)
    {
        $countA = strlen($a->message);
        $countB = strlen($b->message);
        if ($countA === $countB) return 0;
        return ($countA < $countB) ? 1 : -1;
    }

    /**
     * Takes posts and processes required stats
     *
     * @param array $posts
     * @return string
     */
    public static function process($posts)
    {
        $processor = new DataProcessor();
        $processor->sortDataIntoBuckets($posts);


        $result = array();
        $result['average_character_length_of_posts'] = $processor->getAverageCharacterLengthOfPosts();
        $result['longest_posts_by_character_length_per_month'] = $processor->getLongestPostsByCharacterLengthPerMonth();
        $result['total_posts_split_by_weekNumber'] = $processor->getTotalPostsSplitByWeekNumber();
        $result['average_number_of_posts_per_user_per_month'] = $processor->averageNumberOfPostsPerUserPerMonth();

        return json_encode($result);
    }
}
