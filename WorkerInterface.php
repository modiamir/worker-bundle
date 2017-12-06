<?php

namespace Modiamir\WorkerBundle;

interface WorkerInterface
{
    public function execute(array $data);
}