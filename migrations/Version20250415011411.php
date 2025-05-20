<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250415011411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11AC54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX type_id ON incident
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3D03A11AC54C8C93 ON incident (type_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incident ADD CONSTRAINT FK_3D03A11AC54C8C93 FOREIGN KEY (type_id) REFERENCES type_incident (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE plans CHANGE description description LONGTEXT NOT NULL, CHANGE file_path file_path VARCHAR(255) NOT NULL, CHANGE floor floor VARCHAR(50) NOT NULL, CHANGE building building VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE last_update last_update DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects CHANGE description description LONGTEXT NOT NULL, CHANGE start_date start_date DATE NOT NULL, CHANGE end_date end_date DATE NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE priority priority VARCHAR(50) NOT NULL, CHANGE progress progress INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX team_id ON projects
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5C93B3A4296CD8AE ON projects (team_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamations CHANGE description description LONGTEXT NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE priority priority VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamations ADD CONSTRAINT FK_1CAD6B76A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_id ON reclamations
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1CAD6B76A76ED395 ON reclamations (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX author_id ON report
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX incident_id ON report
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX report_structure_id ON report
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report CHANGE author_id author_id INT NOT NULL, CHANGE incident_id incident_id INT NOT NULL, CHANGE report_structure_id report_structure_id INT NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report_structure CHANGE is_required is_required TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX created_by ON reunion
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reunion CHANGE description description LONGTEXT NOT NULL, CHANGE location location VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE created_by created_by INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX plan_id ON tasks
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tasks CHANGE description description LONGTEXT NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members CHANGE joined_at joined_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BAD9A3C8296CD8AE ON team_members (team_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_id ON team_members
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BAD9A3C8A76ED395 ON team_members (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX resource_provider_id ON teams
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX analyst_id ON teams
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams CHANGE description description LONGTEXT NOT NULL, CHANGE analyst_id analyst_id INT NOT NULL, CHANGE resource_provider_id resource_provider_id INT NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams ADD CONSTRAINT FK_96C2225873154ED4 FOREIGN KEY (leader_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX leader_id ON teams
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_96C2225873154ED4 ON teams (leader_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE type_incident CHANGE description description LONGTEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE role role VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE personal_phone personal_phone VARCHAR(20) NOT NULL, CHANGE work_phone work_phone VARCHAR(20) NOT NULL, CHANGE job job VARCHAR(100) NOT NULL, CHANGE last_login last_login DATETIME NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11AC54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_3d03a11ac54c8c93 ON incident
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX type_id ON incident (type_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incident ADD CONSTRAINT FK_3D03A11AC54C8C93 FOREIGN KEY (type_id) REFERENCES type_incident (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE plans CHANGE description description TEXT DEFAULT NULL, CHANGE file_path file_path VARCHAR(255) DEFAULT NULL, CHANGE floor floor VARCHAR(50) DEFAULT NULL, CHANGE building building VARCHAR(100) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT 'ACTIVE', CHANGE last_update last_update DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4296CD8AE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4296CD8AE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects CHANGE description description TEXT DEFAULT NULL, CHANGE start_date start_date DATE DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT 'ACTIVE', CHANGE priority priority VARCHAR(50) DEFAULT 'MEDIUM', CHANGE progress progress INT DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_5c93b3a4296cd8ae ON projects
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX team_id ON projects (team_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamations DROP FOREIGN KEY FK_1CAD6B76A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamations DROP FOREIGN KEY FK_1CAD6B76A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamations CHANGE description description TEXT DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT 'EN_ATTENTE', CHANGE priority priority VARCHAR(50) DEFAULT 'NORMALE', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_1cad6b76a76ed395 ON reclamations
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX user_id ON reclamations (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamations ADD CONSTRAINT FK_1CAD6B76A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report CHANGE author_id author_id INT DEFAULT NULL, CHANGE incident_id incident_id INT DEFAULT NULL, CHANGE report_structure_id report_structure_id INT DEFAULT NULL, CHANGE content content TEXT DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX author_id ON report (author_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX incident_id ON report (incident_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX report_structure_id ON report (report_structure_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report_structure CHANGE is_required is_required TINYINT(1) DEFAULT 1, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reunion CHANGE description description TEXT DEFAULT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT 'PLANIFIEE', CHANGE created_by created_by INT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX created_by ON reunion (created_by)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tasks CHANGE description description TEXT DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT 'En cours' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX plan_id ON tasks (plan_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams DROP FOREIGN KEY FK_96C2225873154ED4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams DROP FOREIGN KEY FK_96C2225873154ED4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams CHANGE description description TEXT DEFAULT NULL, CHANGE analyst_id analyst_id INT DEFAULT NULL, CHANGE resource_provider_id resource_provider_id INT DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT 'ACTIVE', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX resource_provider_id ON teams (resource_provider_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX analyst_id ON teams (analyst_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_96c2225873154ed4 ON teams
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX leader_id ON teams (leader_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams ADD CONSTRAINT FK_96C2225873154ED4 FOREIGN KEY (leader_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8296CD8AE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BAD9A3C8296CD8AE ON team_members
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members CHANGE joined_at joined_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_bad9a3c8a76ed395 ON team_members
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX user_id ON team_members (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE type_incident CHANGE description description TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE role role VARCHAR(11) NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE personal_phone personal_phone VARCHAR(20) DEFAULT NULL, CHANGE work_phone work_phone VARCHAR(20) DEFAULT NULL, CHANGE job job VARCHAR(100) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL
        SQL);
    }
}
