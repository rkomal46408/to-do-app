<?php

class TaskManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function fetchAllTasks($userID) {
        $stmt = $this->pdo->prepare("SELECT * FROM Tasks WHERE userID = ?");
        $stmt->execute([$userID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addOrUpdateTask($taskID, $userID, $title, $description, $dueDate, $priority, $category, $status) {
        if ($taskID) {
            $stmt = $this->pdo->prepare("UPDATE Tasks SET title = ?, description = ?, dueDate = ?, priority = ?, category = ?, status = ? WHERE taskID = ?");
            $stmt->execute([$title, $description, $dueDate, $priority, $category, $status, $taskID]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO Tasks (userID, title, description, dueDate, priority, category, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userID, $title, $description, $dueDate, $priority, $category, 'pending']);
        }
    }

    public function deleteTask($taskID) {
        $stmt = $this->pdo->prepare("DELETE FROM Tasks WHERE taskID = ?");
        $stmt->execute([$taskID]);
    }

    public function toggleTaskStatus($taskID) {
        $stmt = $this->pdo->prepare("UPDATE Tasks SET status = IF(status = 'pending', 'completed', 'pending') WHERE taskID = ?");
        $stmt->execute([$taskID]);
    }
}
