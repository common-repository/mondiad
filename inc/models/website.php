<?php

namespace Mondiad;

class Website {
  const STATUS_PENDING = 'PENDING';
  const STATUS_ACCEPTED = 'ACCEPTED';
  const STATUS_REJECTED = 'REJECTED';
  const STATUS_DELETED = 'DELETED';
  const STATUS_HELD = 'HELD';
  const STATUSES = [Website::STATUS_PENDING, Website::STATUS_ACCEPTED, Website::STATUS_REJECTED, Website::STATUS_DELETED, Website::STATUS_HELD];

  const CAT_ASIAN = 'ASIAN';
  const CAT_BLOGS_FORUMS = 'BLOGS_FORUMS';
  const CAT_CARTOONS_HENTAI = 'CARTOONS_HENTAI';
  const CAT_ENTERTAINMENT = 'ENTERTAINMENT';
  const CAT_EROTIC_SEXY = 'EROTIC_SEXY';
  const CAT_FILE_HOSTING = 'FILE_HOSTING';
  const CAT_GAMES = 'GAMES';
  const CAT_GAY = 'GAY';
  const CAT_GENERAL = 'GENERAL';
  const CAT_IMAGE_GALLERIES = 'IMAGE_GALLERIES';
  const CAT_MANGA_ANIME = 'MANGA_ANIME';
  const CAT_MUSIC_MOVIES = 'MUSIC_MOVIES';
  const CAT_NEWS = 'NEWS';
  const CAT_SPORT = 'SPORT';
  const CAT_STREAMING = 'STREAMING';
  const CAT_TECH_BUSINESS = 'TECH_BUSINESS';
  const CAT_TRANSSEXUAL = 'TRANSSEXUAL';
  const CAT_TUBES = 'TUBES';
  const CAT_VIRTUAL_REALITY = 'VIRTUAL_REALITY';
  const CAT_WEBCAMS = 'WEBCAMS';
  const CATEGORIES = [
    Website::TYPE_MAINSTREAM => [
      Website::CAT_GENERAL => 'General',
      Website::CAT_BLOGS_FORUMS => 'Blogs & Forums',
      Website::CAT_ENTERTAINMENT => 'Entertainment',
      Website::CAT_FILE_HOSTING => 'File Hosting',
      Website::CAT_GAMES => 'Games',
      Website::CAT_MANGA_ANIME => 'Manga & Anime',
      Website::CAT_MUSIC_MOVIES => 'Music & Movies',
      Website::CAT_NEWS => 'News',
      Website::CAT_SPORT => 'Sport',
      Website::CAT_STREAMING => 'Streaming',
      Website::CAT_TECH_BUSINESS => 'Tech & Business'
    ],
    Website::TYPE_ADULT => [
      Website::CAT_GENERAL => 'General',
      Website::CAT_ASIAN => 'Asian',
      Website::CAT_BLOGS_FORUMS => 'Blogs & Forums',
      Website::CAT_CARTOONS_HENTAI => 'Cartoons & Hentai',
      Website::CAT_EROTIC_SEXY => 'Erotic & Sexy',
      Website::CAT_GAY => 'Gay',
      Website::CAT_IMAGE_GALLERIES => 'Image Galleries',
      Website::CAT_TRANSSEXUAL => 'Transsexual',
      Website::CAT_TUBES => 'Tubes',
      Website::CAT_VIRTUAL_REALITY => 'Virtual Reality',
      Website::CAT_WEBCAMS => 'Webcams'
    ]
  ];

  const TYPE_MAINSTREAM = 'MAINSTREAM';
  const TYPE_ADULT = 'ADULT';

  /** @var int */
  public $id;
  /** @var string|null */
  public $name;
  /** @var string|null */
  public $status;
  /** @var string|null */
  public $category;
  /** @var string|null */
  public $websiteCategory;
  /** @var int */
  public $zonesNumber;
  /** @var array */
  public $adZones = []; // array of AdZone
}