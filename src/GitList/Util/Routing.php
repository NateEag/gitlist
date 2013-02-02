<?php

namespace Gitlist\Util;

use Silex\Application;

class Routing
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getBranchTagRegex()
    {
        static $branch_regex = null;

        if ($branch_regex === null) {
            $app = $this->app;

            $branch_names = array();
            $repos = $this->app['git']->getRepositories($this->app['git.repos']);
            $branch_names = array();
            $tag_names = array();
            foreach ($repos as $repo) {
                $repo = $app['git']->getRepository($repo['path']);

                $repo_branch_names = $repo->getBranches();
                if (is_array($repo_branch_names) === TRUE) {
                    $branch_names = array_merge($branch_names,
                                                $repo_branch_names);
                }

                $repo_tag_names = $repo->getTags();
                if (is_array($repo_tag_names) === TRUE) {
                    $tag_names = array_merge($tag_names, $repo_tag_names);
                }
            }

            $names = array_merge($branch_names, $tag_names);

            $branch_regex = '(' . implode('|', $names) . ')';
        }

        return $branch_regex;
    }

    public function getRepositoryRegex()
    {
        static $regex = null;

        if ($regex === null) {
            $app = $this->app;
            $quotedPaths = array_map(
                function ($repo) use ($app) {
                    return preg_quote($app['util.routing']->getRelativePath($repo['path']), '#');
                },
                $this->app['git']->getRepositories($this->app['git.repos'])
            );
            usort($quotedPaths, function ($a, $b) { return strlen($b) - strlen($a); });
            $regex = implode('|', $quotedPaths);
        }

        return $regex;
    }

    /**
     * Strips the base path from a full repository path
     *
     * @param string $repoPath Full path to the repository
     * @return string Relative path to the repository from git.repositories
     */
    public function getRelativePath($repoPath)
    {
        if (strpos($repoPath, $this->app['git.repos']) === 0) {
            $relativePath = substr($repoPath, strlen($this->app['git.repos']));
            return ltrim(strtr($relativePath, '\\', '/'), '/');
        } else {
            throw new \InvalidArgumentException(
                sprintf("Path '%s' does not match configured repository directory", $repoPath)
            );
        }
    }
}
