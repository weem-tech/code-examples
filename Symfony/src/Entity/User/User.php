<?php

namespace App\Entity\User;

use App\Component\Entity\EntityTrait;
use App\Entity\Payment\Payment;
use App\Entity\Shop\Shop;
use App\Entity\Subscription\FeatureSubscription;
use App\Entity\Subscription\Plan;
use App\Entity\Subscription\Subscription;
use App\Entity\Voucher\DraftVoucher;
use App\Entity\Voucher\Voucher;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Serializable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\User\UserRepository")
 * @ORM\Table(name="r_users")
 * @UniqueEntity(fields="email", message="Email already taken")
 */
class User implements UserInterface, Serializable
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    use EntityTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="subscription_plan_id", type="integer", nullable=true)
     */
    protected $subscriptionPlanId;

    /**
     * @ORM\Column(name="free_trial", type="boolean")
     */
    protected $freeTrial;

    /**
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Assert\Email
     */
    protected $email;

    /**
     * @Assert\Regex(pattern="/^((?!\s).)*$/", message="Password should not contain any whitespace characters.")
     * @Assert\Length(max=4096)
     */
    protected $plainPassword;

    /**
     * @ORM\Column(name="password", type="string", length=60)
     */
    protected $password;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     */
    protected $companyName;

    /**
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     */
    protected $street;

    /**
     * @ORM\Column(name="zip", type="string", length=255, nullable=true)
     */
    protected $zip;

    /**
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     * @Assert\Country
     */
    protected $country;

    /**
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active;

    /**
     * @ORM\Column(name="frozen", type="boolean", nullable=true)
     */
    protected $frozen;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\Column(name="confirmation_token", type="string", length=128, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\Column(name="salt", type="string", nullable=true)
     */
    protected $salt;

    /**
     * @ORM\Column(name="roles", type="array")
     */
    protected $roles;

    /**
     * @ORM\Column(type="datetime", nullable = true)
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vatId;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Shop\Shop", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    protected $shops;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Voucher\Voucher", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    protected $vouchers;
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Payment\Payment", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    protected $payment;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Subscription\Subscription", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    protected $subscriptions;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Subscription\Plan", inversedBy="users")
     * @ORM\JoinColumn(name="subscription_plan_id")
     */
    private $subscriptionPlan;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Voucher\DraftVoucher", mappedBy="user")
     */
    private $draftVouchers;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Subscription\FeatureSubscription", mappedBy="user")
     */
    private $featureSubscriptions;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->confirmationToken = hash('sha256', random_bytes(256) . microtime());
        $this->active = false;
        $this->frozen = false;
        $this->pluginPopup = true;
        $this->pluginAutocomplete = true;
        $this->roles = [];
        $this->shops = new ArrayCollection();
        $this->vouchers = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->draftVouchers = new ArrayCollection();
        $this->featureSubscriptions = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Shop[]
     */
    public function getShops(): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('deletedAt', null));
        return $this->shops->matching($criteria);
    }

    /**
     * @param Shop $shop
     * @return User
     */
    public function addShop(Shop $shop): self
    {
        if (!$this->shops->contains($shop)) {
            $this->shops[] = $shop;
            $shop->setUser($this);
        }

        return $this;
    }

    /**
     * @param Shop $shop
     * @return Shop
     */
    public function removeShop(Shop $shop): self
    {
        if ($this->shops->contains($shop)) {
            $this->shops->removeElement($shop);
            if ($shop->getUser() === $this) {
                $shop->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers(): Collection
    {
        return $this->vouchers;
    }

    /**
     * @param Voucher $voucher
     * @return User
     */
    public function addVoucher(Voucher $voucher): self
    {
        if (!$this->vouchers->contains($voucher)) {
            $this->vouchers[] = $voucher;
            $voucher->setUser($this);
        }

        return $this;
    }

    /**
     * @param Voucher $voucher
     * @return User
     */
    public function removeVoucher(Voucher $voucher): self
    {
        if ($this->vouchers->contains($voucher)) {
            $this->vouchers->removeElement($voucher);
            if ($voucher->getUser() === $this) {
                $voucher->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return null|Payment
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     * @return User
     */
    public function setPayment(Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @return Collection|Subscription[]
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * @param Subscription $subscription
     * @return User
     */
    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions[] = $subscription;
            $subscription->setUser($this);
        }

        return $this;
    }

    /**
     * @param Subscription $subscription
     * @return User
     */
    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->contains($subscription)) {
            $this->subscriptions->removeElement($subscription);
            // set the owning side to null (unless already changed)
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionPlanId()
    {
        return $this->subscriptionPlanId;
    }

    /**
     * @param int|null $subscriptionPlanId
     * @return User
     */
    public function setSubscriptionPlanId(?int $subscriptionPlanId): self
    {
        $this->subscriptionPlanId = $subscriptionPlanId;

        return $this;
    }

    /**
     * @return Plan|null
     */
    public function getSubscriptionPlan(): ?Plan
    {
        return $this->subscriptionPlan;
    }

    /**
     * @param Plan|null $subscriptionPlan
     * @return User
     */
    public function setSubscriptionPlan(?Plan $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getUsername(): ?string
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param $plainPassword
     * @return User
     */
    public function setPlainPassword($plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return User
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     * @return User
     */
    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return User
     */
    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     * @return User
     */
    public function setZip(string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return User
     */
    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return User
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     * @return User
     */
    public function setActive($active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param mixed $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * @param mixed $confirmationToken
     * @return User
     */
    public function setConfirmationToken($confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param mixed $passwordRequestedAt
     * @return User
     */
    public function setPasswordRequestedAt($passwordRequestedAt): self
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param mixed $salt
     * @return User
     */
    public function setSalt($salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $roles = $this->roles;

        if (empty($roles)) {
            // we need to make sure to have at least one role
            $roles[] = self::ROLE_DEFAULT;
        }
        return array_unique($roles);
    }

    /**
     * {@inheritdoc}
     */
    public function setRoles(array $roles)
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === self::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     *
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->email,
            $this->password,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->email,
            $this->password,
            ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * @return bool|null
     */
    public function getFreeTrial(): ?bool
    {
        return $this->freeTrial;
    }

    /**
     * @param bool $freeTrial
     * @return User
     */
    public function setFreeTrial(bool $freeTrial): self
    {
        $this->freeTrial = $freeTrial;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getFrozen(): ?bool
    {
        return $this->frozen;
    }

    /**
     * @param bool $frozen
     * @return User
     */
    public function setFrozen(bool $frozen): self
    {
        $this->frozen = $frozen;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     * @return User
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    /**
     * @param string|null $vatId
     * @return User
     */
    public function setVatId(?string $vatId): self
    {
        $this->vatId = $vatId;

        return $this;
    }

    /**
     * @return Collection|DraftVoucher[]
     */
    public function getDraftVouchers(): Collection
    {
        return $this->draftVouchers;
    }

    /**
     * @param DraftVoucher $draftVoucher
     * @return $this
     */
    public function addDraftVoucher(DraftVoucher $draftVoucher): self
    {
        if (!$this->draftVouchers->contains($draftVoucher)) {
            $this->draftVouchers[] = $draftVoucher;
            $draftVoucher->setUser($this);
        }

        return $this;
    }

    /**
     * @param DraftVoucher $draftVoucher
     * @return $this
     */
    public function removeDraftVoucher(DraftVoucher $draftVoucher): self
    {
        if ($this->draftVouchers->contains($draftVoucher)) {
            $this->draftVouchers->removeElement($draftVoucher);
            // set the owning side to null (unless already changed)
            if ($draftVoucher->getUser() === $this) {
                $draftVoucher->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FeatureSubscription[]
     */
    public function getFeatureSubscriptions(): Collection
    {
        return $this->featureSubscriptions;
    }

    /**
     * @param FeatureSubscription $featureSubscription
     * @return $this
     */
    public function addFeatureSubscription(FeatureSubscription $featureSubscription): self
    {
        if (!$this->featureSubscriptions->contains($featureSubscription)) {
            $this->featureSubscriptions[] = $featureSubscription;
            $featureSubscription->setUser($this);
        }

        return $this;
    }

    /**
     * @param FeatureSubscription $featureSubscription
     * @return $this
     */
    public function removeFeatureSubscription(FeatureSubscription $featureSubscription): self
    {
        if ($this->featureSubscriptions->contains($featureSubscription)) {
            $this->featureSubscriptions->removeElement($featureSubscription);
            // set the owning side to null (unless already changed)
            if ($featureSubscription->getUser() === $this) {
                $featureSubscription->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getFeatures()
    {
        $features = [];
        foreach ($this->featureSubscriptions as $featureSubscription) {
            if ($featureSubscription->getActive()) {
                $features[$featureSubscription->getShop()->getId()][$featureSubscription->getFeature()->getId()] = [
                    'freeTrial' => $featureSubscription->getFreeTrial(),
                    'startedDate' => $featureSubscription->getStartedDate(),
                    'expiredDate' => $featureSubscription->getExpiredDate()
                ];
            }
        }

        return $features;
    }
}
