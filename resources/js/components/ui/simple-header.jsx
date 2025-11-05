import React from 'react'; 
import { Sheet, SheetContent, SheetFooter } from '@/components/ui/sheet';
import { Button, buttonVariants } from '@/components/ui/button';
import { MenuToggle } from '@/components/ui/menu-toggle';

export function SimpleHeader() {
	const [open, setOpen] = React.useState(false);

	const links = [
		{
			label: 'Features',
			href: '#features',
		},
		{
			label: 'About',
			href: '/about-us',
		},
		{
			label: 'FAQ',
			href: '#faq',
		},
		
	];

	const handleSmoothScroll = (e, href) => {
		// Only handle anchor links (starting with #)
		if (href.startsWith('#')) {
			e.preventDefault();
			const target = document.querySelector(href);
			if (target) {
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}
	};

    return (
        <header
            className="fixed top-0 left-0 right-0 z-50 w-full px-6 py-3">
            <nav
                className="mx-auto flex h-12 max-w-fit items-center justify-between gap-8 rounded-full border bg-background/80 px-12 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-background/70">
				<div className="flex items-center gap-2">
				<a href="/" aria-label="Go to home">
					<img src="/images/Logo.png" alt="Logo" className="h-6" />
				</a>
				</div>
                <div className="hidden items-center gap-6 lg:flex">
					{links.map((link) => (
						<a 
							key={link.label}
							className={buttonVariants({ variant: 'ghost' })} 
							href={link.href}
							onClick={(e) => handleSmoothScroll(e, link.href)}
						>
							{link.label}
						</a>
					))}
                    <a href="/download">
						<Button size="sm" className='rounded-full'>Download</Button>
					</a>
				</div>
				<Sheet open={open} onOpenChange={setOpen}>
                    <Button size="icon" variant="outline" className="lg:hidden rounded-full">
						<MenuToggle strokeWidth={2.5} open={open} onOpenChange={setOpen} className="size-6" />
					</Button>
					<SheetContent
                        className="bg-background/95 supports-[backdrop-filter]:bg-background/80 gap-0 backdrop-blur-lg"
                        showClose={false}
                        side="left">
						<div className="grid gap-y-2 overflow-y-auto px-4 pt-12 pb-5">
							{links.map((link) => (
								<a
									key={link.label}
                                    className={buttonVariants({
										variant: 'ghost',
										className: 'justify-start',
									})}
                                    href={link.href}
									onClick={(e) => {
										handleSmoothScroll(e, link.href);
										setOpen(false); // Close mobile menu after clicking
									}}
								>
									{link.label}
								</a>
							))}
						</div>
						<SheetFooter>
							<a href="/download" className="w-full">
								<Button className="w-full rounded-full">Download App</Button>
							</a>
						</SheetFooter>
					</SheetContent>
				</Sheet>
			</nav>
        </header>
    );
}
