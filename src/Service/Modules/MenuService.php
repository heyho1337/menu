<?php

namespace App\Service\Modules;

use App\Entity\EvcMenu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Service\Modules\LangService;

class MenuService
{

    protected array $menuList;
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LangService $langService,
        protected RequestStack $requestStack
    ){
        $this->menuList = $this->entityManager
            ->getRepository(EvcMenu::class)
            ->findBy(
                ['menu_aktiv' => 1],
                ['menu_sorrend' => 'ASC']
            );
    }

    public function getAllMenus(): array
    {
        return $this->menuList;
    }

    public function getMenu(string $menu_fajlnev): EvcMenu|false
    {
        $foundObject = null;
        foreach ($this->menuList as $menu) {
            if ($menu->getMenuFajlnev() === $menu_fajlnev) {
                $foundObject = $menu;
                break;
            }
        }
        if ($foundObject !== null) {
            return $foundObject;
        }

        return false;
    }

    public function getMenuById(int $id): EvcMenu|false
    {
        $foundObject = null;
        foreach ($this->menuList as $menu) {
            if ($menu->getMenuId() === $id) {
                $foundObject = $menu;
                break;
            }
        }
        if ($foundObject !== null) {
            return $foundObject;
        }

        return false;
    }

    public function getMenuByAlias(string $alias): EvcMenu|false
    {
        $foundObject = null;
        foreach ($this->menuList as $menu) {
            if ($menu->getMenuAlias() === $alias) {
                $foundObject = $menu;
                break;
            }
        }
        if ($foundObject !== null) {
            return $foundObject;
        }

        return false;
    }

    public function getNavList(string $menu_pozicio, ?int $parent = 0): array
    {
        return array_filter($this->menuList, function ($menu) use ($menu_pozicio, $parent) {
            return $menu->getMenuPozicio() == $menu_pozicio && $menu->getMenuSzuloId() == $parent;
        });
    }

    public function getAliasWithParent(EvcMenu $row): string
    {
        $menu_alias = "";
        $currentMenu = $row;
		
		if($row->getMenuTipus() === 'cikk'){
			return '';
		}

        if($currentMenu->getMenuSzuloId() != 0){
            $parentList = array_filter($this->menuList, function ($menu) use ($currentMenu) {
                return $menu->getMenuId() == $currentMenu->getMenuSzuloId();
            });

            $parent = reset($parentList);
            $menu_alias = $parent->getMenuAlias();
        }

        return $menu_alias;
    }

    public function getNav(array $navList, ?int $sub = 1, int $showParent = 1): string
    {
        $result = "";
        foreach($navList as $row){
            $target="";
            switch($row->getMenuTipus()){
                case 'kulso url':
                    $target = "target='_blank'";
                    $alias = $row->getMenuUrl();
                    break;
                case 'fooldal':
                    $alias = "";
                    break;
                case 'fajl':
                    $alias = "/vision/oop/uploaded_images/menu/{$row->getMenuFajl()}";
                    $target = "target='_blank'";
                    break;
                case 'ProductCategory':
                case 'Product':
                    $alias = $row->getMenuAlias();
                    break;
                case 'blogcikk':
                    $blogMenu = array_filter($this->menuList, function ($menu){
                        return $menu->getMenuFajlnev() === 'blog';
                    });
                    $alias = "{$blogMenu->getMenuAlias()}/{$row->getMenuAlias()}";
                    break;
                default:
                    $alias = "{$row->getMenuAlias()}";
                    break;
            }
            $urlWithParent = $this->getAliasWithParent($row);

            if($row->getMenuTipus() !== 'fooldal'){
                $menu_alias = "href='{$urlWithParent}/{$alias}/'";
            }
            else{
                $menu_alias = "href='/'";
            }

            if($this->getPath() === $row->getMenuAlias()){
                $active = "active";
            }
            else{
                $active = '';
            }

            if($sub === 1) {
                $subNav = $this->getNav($this->getNavList($row->getMenuPozicio(), $row->getMenuId()));

                if ($subNav) {
                    $subMenu = "<input type='checkbox'/><svg width='8' height='12' viewBox='0 0 8 12' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M1.70697 11.9496L7.41397 6.24264L1.70697 0.535645L0.292969 1.94964L4.58597 6.24264L0.292969 10.5356L1.70697 11.9496Z' fill='#00132E'/>
                    </svg><ul>{$subNav}</ul>";
                } else {
                    $subMenu = "";
                }
            }
            else{
                $subNav = "";
                $subMenu = "";
            }
			if($showParent === 1){
            $result.= "<li class='{$active}'><a title='{$row->getMenuTitle()}' {$menu_alias} {$target}><span>{$row->getMenuTitle()}</span></a>	
				{$subMenu}
				</li>";
			}
			elseif($showParent === 0 && $subNav){
				$result.= "<li class='{$active}'>	
				{$subMenu}
				</li>";
			}
			elseif($showParent === 0 && !$subNav){
				$result.= "<li class='{$active}'><a title='{$row->getMenuTitle()}' {$menu_alias} {$target}><span>{$row->getMenuTitle()}</span></a>	
				{$subMenu}
				</li>";
			}
        }
        return $result;
    }

    public function getPath(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $path = explode("/",$request->getPathInfo());
            return $path[1];
        }

        return null;
    }

}