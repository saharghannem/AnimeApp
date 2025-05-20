CREATE TABLE quiz_score (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    quiz_type VARCHAR(20) NOT NULL,
    date DATETIME NOT NULL,
    INDEX IDX_quiz_score_user (user_id),
    CONSTRAINT FK_quiz_score_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
